<?php

namespace Fuzz\ApiServer\Routing;

use Fuzz\Data\Traits\Transformations;
use Fuzz\Data\Transformations\Serialization\DefaultArrayTransformer;
use Illuminate\Http\Request;
use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\Auth\Policies\ChecksGatePolicies;
use Fuzz\MagicBox\Utility\ChecksRelations;
use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Fuzz\Auth\Policies\RepositoryModelPolicyInterface;
use LucaDegasperi\OAuth2Server\Exceptions\NoActiveAccessTokenException;
use Fuzz\HttpException\BadRequestHttpException;
use Fuzz\HttpException\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResourceController
 *
 * @package Fuzz\Agency\Routing
 */
class ResourceController extends Controller
{
	use Transformations, ChecksGatePolicies, ChecksRelations;

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
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function index(Repository $repository, Request $request): Response
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		if ($request->get('paginate', 'true') === 'false' && $this->policy()->unpaginatedIndex($repository)) {
			$paginator = $repository->all();
		} else {
			$paginator = $repository->paginate($this->getPerPage());
		}

		$transformed = $this->transformEntity($paginator);

		return $this->succeed($transformed);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
	 */
	public function store(Repository $repository, Request $request): Response
	{
		if ($request->get('id')) {
			throw new BadRequestHttpException('You cannot create a resource with an id.', ['id' => [sprintf('You cannot create a resource with an id.')]]);
		}

		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$created = $repository->create();

		$transformed = $this->transformEntity($created);

		return $this->created($transformed);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function show(Repository $repository, Request $request): Response
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$resource = $repository->read();

		$transformed = $this->transformEntity($resource);

		return $this->succeed($transformed);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function update(Repository $repository, Request $request): Response
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$resource = $repository->save();

		$transformed = $this->transformEntity($resource);

		return $this->succeed($transformed);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository $repository
	 * @param \Illuminate\Http\Request            $request
	 * @param int                                 $id
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function destroy(Repository $repository, Request $request, int $id): Response
	{
		$this->checkAndApplyPolicy(__FUNCTION__, $repository);

		$this->transformer = DefaultArrayTransformer::class;
		$transformed = $this->transformEntity(['status' => $repository->delete($id)]);

		return $this->succeed($transformed);
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
			if (! $this->policy()->{$action}($repository)) {
				throw new AccessDeniedHttpException;
			}
		} catch (NoActiveAccessTokenException $exception) {
			throw new AccessDeniedHttpException;
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
}
