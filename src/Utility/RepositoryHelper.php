<?php

namespace Fuzz\ApiServer\Utility;

use Fuzz\ApiServer\Exceptions\JsonValidationException;
use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\MagicBox\EloquentRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Respond;


/**
 * Class RepositoryHelper
 *
 * @package Fuzz\ApiServer\Utility
 */
trait RepositoryHelper
{
	/**
	 * Get a "simple" paginated list of entities. Simple pagination does not
	 * include totals. The trade off being it requires less SQL queries.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param int                             $per_page
	 *
	 * @return Paginator
	 */
	protected function getSimplePaginatedEntities(Repository $repository, int $per_page): Paginator
	{
		return $repository->paginate($per_page, true);
	}

	/**
	 * Get a list of all entities or a paginated list. Useful method when you don't
	 * know if you'll have to paginate or not.
	 *
	 * @example:
	 *
	 *         public function index(Repository $repository, Request $request)
	 *         {
	 *              $entities = $this->getAllOrPaginatedEntities($repository, $request->get('per_page'));
	 *         }
	 *
	 * If the request didn't ask for a paginated list then all will be returned,
	 * if it did, then it will be paginated.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param null | integer                  $per_page
	 *
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator | \Illuminate\Contracts\Pagination\Paginator |
	 *                                                               \Illuminate\Database\Eloquent\Collection
	 */
	protected function getAllOrPaginatedEntities(Repository $repository, int $per_page = null)
	{
		return ($per_page)
			? $this->getPaginatedEntities($repository, $per_page)
			: $this->getEntities($repository);
	}

	/**
	 * Get a paginated list by the $per_page parameter.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param int                             $per_page
	 *
	 * @return Paginator
	 */
	protected function getPaginatedEntities(Repository $repository, int $per_page): Paginator
	{
		return $repository->paginate($per_page);
	}

	/**
	 * Get all the entities.
	 *
	 * @param Repository | EloquentRepository $repository
	 *
	 * @return Collection
	 */
	protected function getEntities(Repository $repository): Collection
	{
		return $repository->all();
	}

	/**
	 * Get a single entity by it's id.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param mixed                           $id
	 *
	 * @return Model
	 */
	protected function getEntity(Repository $repository, $id): Model
	{
		return $repository->read($id);
	}

	/**
	 * Create and store an entity.
	 *
	 * @param Repository | EloquentRepository $repository
	 *
	 * @return Model
	 */
	protected function createEntity(Repository $repository): Model
	{
		return $repository->create();
	}

	/**
	 * Update a record by it's id.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param mixed                           $id
	 *
	 * @return Model
	 */
	protected function updateEntity(Repository $repository, $id): Model
	{
		return $repository->save($id);
	}

	/**
	 * Delete a record by it's id.
	 *
	 * @param Repository | EloquentRepository $repository
	 * @param mixed                           $id
	 *
	 * @return bool
	 */
	protected function deleteEntity(Repository $repository, $id): bool
	{
		return $repository->delete($id);
	}

	/**
	 * Create many records given a request that passes many items.
	 *
	 * @param Repository | EloquentRepository $repository
	 *
	 * @return Collection
	 */
	protected function createManyEntities(Repository $repository): Collection
	{
		$rules = [
			'*' => 'bail|array',
		];

		$validator = app(ValidationFactory::class)->make($repository->getInput(), $rules);

		if ($validator->fails() || ! $repository->isManyOperation()) {
			$this->throwJsonValidationException($validator);
		}

		return $repository->createMany();
	}

	/**
	 * Update many records given they have a primary key.
	 *
	 * @param Repository | EloquentRepository $repository
	 *
	 * @return Collection
	 */
	protected function updateManyEntities(Repository $repository): Collection
	{
		$model = $repository->getModelClass();

		$rules = [
			'*'                               => 'bail|array',
			'*.' . (new $model)->getKeyName() => 'bail|required',
		];

		$validator = app(ValidationFactory::class)->make($repository->getInput(), $rules);

		if ($validator->fails() || ! $repository->isManyOperation()) {
			$this->throwJsonValidationException($validator);
		}

		return $repository->updateMany();
	}

	/**
	 * Throw the failed validation exception.
	 *
	 * @param Validator | \Illuminate\Validation\Validator $validator
	 *
	 * @throws JsonValidationException
	 */
	protected function throwJsonValidationException(Validator $validator)
	{
		throw new JsonValidationException($validator,
			new JsonResponse($validator->errors()->getMessages(), Respond::HTTP_BAD_REQUEST)
		);
	}
}