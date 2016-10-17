<?php

namespace Fuzz\ApiServer\Routing;

use Illuminate\Http\Request;
use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\Auth\Policies\ChecksGatePolicies;
use Fuzz\MagicBox\Utility\ChecksRelations;
use Fuzz\ApiServer\Utility\SerializesData;
use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Fuzz\Data\Serialization\FuzzModelTransformer;
use Fuzz\Data\Serialization\FuzzArrayTransformer;
use Fuzz\Auth\Policies\RepositoryModelPolicyInterface;
use LucaDegasperi\OAuth2Server\Exceptions\NoActiveAccessTokenException;
use Fuzz\HttpException\BadRequestHttpException;
use Fuzz\HttpException\AccessDeniedHttpException;

/**
 * Class ResourceController
 *
 * @package Fuzz\Agency\Routing
 */
class ResourceController extends Controller
{
	use SerializesData, ChecksGatePolicies, ChecksRelations;

	/**
	 * Default response format
	 *
	 * @var string
	 */
	const DEFAULT_FORMAT = 'json';

	/**
	 * Require a model policy to be present.
	 *
	 * Laravel will throw an InvalidArgumentException if a policy is not defined. We require a policy to be
	 * defined for every resource.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 */
	public function requirePolicy(Repository $repository)
	{
		$model_class = $repository->getModelClass();

		$policy = $this->setPolicyClass($model_class);

		if (! ($policy instanceof RepositoryModelPolicyInterface)) {
			throw new \LogicException(get_class($policy) . ' does not implement ' . RepositoryModelPolicyInterface::class . '.');
		}
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(Repository $repository, Request $request)
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$model_class = $repository->getModelClass();

		// @todo needs to be better
		if ($request->get('paginate', 'true') === 'false' && $this->policy()->unpaginatedIndex($repository)) {
			$paginator = $repository->all();
		} else {
			$paginator = $repository->paginate($this->getPerPage());
		}

		$serialized = $this->serializeCollection($paginator, $this->modelTransformer($model_class), $request->get('format', self::DEFAULT_FORMAT));

		return $this->succeed($serialized);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
	 */
	public function store(Repository $repository, Request $request)
	{
		if ($request->get('id')) {
			throw new BadRequestHttpException('You cannot create a resource with an id.', ['id' => [sprintf('You cannot create a resource with an id.')]]);
		}

		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$created = $repository->create();

		$serialized = $this->serialize($created, $this->modelTransformer($repository->getModelClass()), $request->get('format', self::DEFAULT_FORMAT));

		return $this->created($serialized);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(Repository $repository, Request $request)
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$resource = $repository->read();

		$serialized = $this->serialize($resource, $this->modelTransformer($repository->getModelClass()), $request->get('format', self::DEFAULT_FORMAT));

		return $this->succeed($serialized);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(Repository $repository, Request $request)
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$resource = $repository->save();

		$serialized = $this->serialize($resource, $this->modelTransformer($repository->getModelClass()), $request->get('format', self::DEFAULT_FORMAT));

		return $this->succeed($serialized);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(Repository $repository, Request $request)
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$serialized = $this->serialize(['status' => $repository->delete()], FuzzArrayTransformer::class, $request->get('format', self::DEFAULT_FORMAT));

		return $this->succeed($serialized);
	}

	/**
	 * Check policy for a CRUD method and allow it to apply modifications on the repository
	 *
	 * @param string                              $action
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @return bool
	 */
	public function checkAndApplyPolicy($action, Repository $repository)
	{
		$this->requirePolicy($repository);

		try {
			if (! $this->policy()->{$action}($repository)) { // @todo fix
				throw new AccessDeniedHttpException('Access denied.');
			}
		} catch (NoActiveAccessTokenException $exception) {
			throw new AccessDeniedHttpException('Access denied.');
		}

		$model_class = $repository->getModelClass();
		$input       = $repository->getInput();
		$instance    = $repository->exists() ? $repository->read() : new $model_class;

		if (! is_a($instance, MagicBoxResource::class, true)) {
			throw new AccessDeniedHttpException;
		}

		// Recursively check ACL for creation/update on all related models
		if (! in_array(
			$action, [
				'update',
				'store',
			]
		)
		) {
			return true;
		}

		$this->validateCascadingRelations($instance, $input, $repository);

		$repository->setInput($input);

		return true;
	}

	/**
	 * Validate cascading relations are not violated by nested rules.
	 *
	 * @param \Fuzz\MagicBox\Contracts\MagicBoxResource|\Illuminate\Database\Eloquent\Model $model
	 * @param array                                                                         $input
	 * @param \Fuzz\MagicBox\Contracts\Repository                                           $repository
	 * @todo support more relationship types, such as polymorphic ones!
	 */
	public function validateCascadingRelations(MagicBoxResource $model, &$input, Repository $repository)
	{
		foreach ($input as $key => &$value) {
			// Scalar values can be skipped
			if (is_scalar($value)
				|| ! method_exists($model, $key)
				|| ! ($relation = $this->isRelation($model, $key, get_class($model)))
			) {
				continue;
			}

			/**
			 * The relation and model are of known types.
			 *
			 * @var \Illuminate\Database\Eloquent\Relations\Relation $relation
			 * @var \Fuzz\MagicBox\Contracts\MagicBoxResource        $related
			 */
			$related = $relation->getRelated();

			// Only Resource relations can be cascaded through
			if (! is_a($related, MagicBoxResource::class, true)) {
				unset($input[$key]);
				continue;
			}

			switch (class_basename($relation)) {
				case 'HasMany':
				case 'BelongsToMany':
					foreach ($value as $subkey => &$subvalue) {
						$this->checkRelatedAclFromInput($related, $subvalue, $repository, $model);
					}
					break;
				case 'HasOne':
				case 'BelongsTo':
					$this->checkRelatedAclFromInput($related, $value, $repository, $model);
					break;
				default:
					unset($input[$key]);
					break;
			}
		}
	}

	/**
	 * Check relationship ACL values for simple insert/update abilities.
	 *
	 * @param \Fuzz\MagicBox\Contracts\MagicBoxResource|\Illuminate\Database\Eloquent\Model $related
	 * @param array                                                                         $input
	 * @param \Fuzz\MagicBox\Contracts\Repository                                           $repository
	 * @param \Fuzz\MagicBox\Contracts\MagicBoxResource|\Illuminate\Database\Eloquent\Model $parent
	 */
	public function checkRelatedAclFromInput(MagicBoxResource $related, &$input, Repository $repository, MagicBoxResource $parent)
	{
		$key_name            = $related->getKeyName();
		$related_model_class = get_class($related);

		/** @var \Fuzz\Auth\Policies\RepositoryModelPolicyInterface $policy */
		$policy = policy($related_model_class);

		if (isset($input[$key_name])) {
			$related = $related_model_class::findOrFail($input[$key_name]);
			if (! $policy->updateNested($repository, $related, $parent, $input)) {
				$input = array_only($input, [$key_name]);
			}
		} elseif (! $policy->storeNested($repository, $related, $parent, $input)) {
			throw new AccessDeniedHttpException;
		}

		$this->validateCascadingRelations($related, $input, $repository);
	}

	/**
	 * Find the model transformer to use for serialization
	 *
	 * @param string $model_class
	 * @return mixed
	 */
	public function modelTransformer($model_class)
	{
		$instance = new $model_class;

		return isset($instance->model_transformer) ? $instance->model_transformer : FuzzModelTransformer::class;
	}
}
