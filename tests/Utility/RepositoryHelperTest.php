<?php

namespace Tests\Utility;

use Fuzz\ApiServer\Exceptions\JsonValidationException;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Utility\RepositoryHelper;
use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\MagicBox\EloquentRepository;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use Mockery\Mock;


class RepositoryHelperTest extends AppTestCase
{
	/**
	 * @var StubController
	 */
	protected $controller;

	/**
	 * @var int
	 */
	protected $per_page = 10;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var Mock | EloquentRepository
	 */
	protected $mockRepository;

	public function setUp()
	{
		parent::setUp();

		$this->mockRepository = \Mockery::mock(EloquentRepository::class);

		$this->model = new class extends Model
		{

		};

		$this->controller = new StubController();
	}

	/**
	 * @test
	 *
	 * @see Controller::getSimplePaginatedEntities()
	 */
	public function testGetSimplePaginatedEntities()
	{
		$this->mockRepository->shouldReceive('paginate')->once()->with($this->per_page, true)
			->andReturn(new Paginator(new Collection([1, 2, 3, 4]), $this->per_page));

		$this->assertInstanceOf(Paginator::class,
			$this->controller->getSimplePaginatedEntities($this->mockRepository, $this->per_page));
	}

	/**
	 * @test
	 *
	 * @see Controller::getAllOrPaginatedEntities()
	 */
	public function testGetAllOrPaginatedEntitiesAsPaginated()
	{
		$items = new Collection([1, 2, 3, 4]);
		$this->mockRepository->shouldReceive('paginate')->once()->with($this->per_page)
			->andReturn(new LengthAwarePaginator($items, $items->count(), $this->per_page));

		$this->assertInstanceOf(LengthAwarePaginator::class,
			$this->controller->getAllOrPaginatedEntities($this->mockRepository, $this->per_page));
	}

	/**
	 * @test
	 *
	 * @see Controller::getAllOrPaginatedEntities()
	 */
	public function testGetAllOrPaginatedEntitiesAsEntities()
	{
		$this->mockRepository->shouldReceive('all')->once()->andReturn(new Collection());

		$this->assertInstanceOf(Collection::class, $this->controller->getAllOrPaginatedEntities($this->mockRepository));
	}

	/**
	 * @test
	 *
	 * @see Controller::getPaginatedEntities()
	 */
	public function testGetPaginatedEntities()
	{
		$items = new Collection([1, 2, 3, 4]);
		$this->mockRepository->shouldReceive('paginate')->once()->with($this->per_page)
			->andReturn(new LengthAwarePaginator($items, $items->count(), $this->per_page));

		$this->assertInstanceOf(LengthAwarePaginator::class,
			$this->controller->getPaginatedEntities($this->mockRepository, $this->per_page));
	}

	/**
	 * @test
	 *
	 * @see Controller::getEntities()
	 */
	public function testGetEntities()
	{
		$this->mockRepository->shouldReceive('all')->once()->andReturn(new Collection());

		$this->assertInstanceOf(Collection::class, $this->controller->getEntities($this->mockRepository));
	}

	/**
	 * @test
	 *
	 * @see Controller::getEntity()
	 */
	public function testGetEntity()
	{
		$this->mockRepository->shouldReceive('read')->once()->with(1)->andReturn($this->model);

		$this->assertInstanceOf(Model::class, $this->controller->getEntity($this->mockRepository, 1));
	}

	/**
	 * @test
	 *
	 * @see Controller::createEntity()
	 */
	public function testCreateEntity()
	{
		$this->mockRepository->shouldReceive('create')->once()->andReturn($this->model);

		$this->assertInstanceOf(Model::class, $this->controller->createEntity($this->mockRepository));
	}

	/**
	 * @test
	 *
	 * @see Controller::updateEntity()
	 */
	public function testUpdateEntity()
	{
		$this->mockRepository->shouldReceive('save')->once()->with(1)->andReturn($this->model);

		$this->assertInstanceOf(Model::class, $this->controller->updateEntity($this->mockRepository, 1));
	}

	/**
	 * @test
	 *
	 * @see Controller::updateManyEntities()
	 */
	public function testUpdateManyEntities()
	{
		$many = [
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4],
		];
		$this->mockRepository->shouldReceive('getModelClass')->once()->andReturn(get_class($this->model));
		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($many);
		$this->mockRepository->shouldReceive('isManyOperation')->once()->andReturn(true);
		$this->mockRepository->shouldReceive('updateMany')->once()->andReturn(new Collection($many));


		$this->assertInstanceOf(Collection::class, $this->controller->updateManyEntities($this->mockRepository));
	}

	/**
	 * @test
	 *
	 * @see Controller::updateManyEntities()
	 */
	public function testUpdateManyEntitiesValidatorThrows()
	{
		$invalid = [
			['name' => 'blah'],
			['name' => 'boo'],
			['name' => 'naaa'],
			['name' => 'asdf'],
		];
		$this->mockRepository->shouldReceive('getModelClass')->once()->andReturn(get_class($this->model));
		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($invalid);

		$this->expectException(JsonValidationException::class);
		$this->controller->updateManyEntities($this->mockRepository);
	}

	/**
	 * @test
	 *
	 * @see Controller::updateManyEntities()
	 */
	public function testUpdateManyEntitiesIsManyOperationThrows()
	{
		$invalid = [
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4],
		];
		$this->mockRepository->shouldReceive('getModelClass')->once()->andReturn(get_class($this->model));
		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($invalid);
		$this->mockRepository->shouldReceive('isManyOperation')->once()->andReturn(false);

		$this->expectException(JsonValidationException::class);
		$this->controller->updateManyEntities($this->mockRepository);
	}

	/**
	 * @test
	 *
	 * @see Controller::createManyEntities()
	 */
	public function testCreateManyEntities()
	{
		$many = [
			['name' => 'blah'],
			['name' => 'boo'],
			['name' => 'naaa'],
			['name' => 'asdf'],
		];

		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($many);
		$this->mockRepository->shouldReceive('isManyOperation')->once()->andReturn(true);
		$this->mockRepository->shouldReceive('createMany')->once()->andReturn(new Collection($many));

		$this->assertInstanceOf(Collection::class, $this->controller->createManyEntities($this->mockRepository));
	}

	/**
	 * @test
	 *
	 * @see Controller::createManyEntities()
	 */
	public function testCreateManyEntitiesValidatorThrows()
	{
		$invalid = [
			'name'       => 'blah',
			'first_name' => 'boo',
		];

		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($invalid);

		$this->expectException(JsonValidationException::class);
		$this->controller->createManyEntities($this->mockRepository);
	}

	/**
	 * @test
	 *
	 * @see Controller::createManyEntities()
	 */
	public function testCreateManyEntitiesIsManyOperationThrows()
	{
		$invalid = [
			[
				'blah',
				'blah',
			],
		];
		$this->mockRepository->shouldReceive('getInput')->once()->andReturn($invalid);
		$this->mockRepository->shouldReceive('isManyOperation')->once()->andReturn(false);

		$this->expectException(JsonValidationException::class);
		$this->controller->createManyEntities($this->mockRepository);
	}

	/**
	 * @test
	 *
	 * @see Controller::deleteEntity()
	 */
	public function testDeleteEntity()
	{
		$this->mockRepository->shouldReceive('delete')->once()->with(1)->andReturn(true);

		$this->assertTrue($this->controller->deleteEntity($this->mockRepository, 1));
	}
}

/**
 * Class BaseController
 *
 * Used as the base controller to extend from.
 */
class BaseController extends Controller
{
	use RepositoryHelper;
}


/**
 * Class StubController
 *
 * Makes protected methods public to test easily.
 */
class StubController extends BaseController
{

	public function getSimplePaginatedEntities(Repository $repository, int $per_page): PaginatorContract
	{
		return parent::getSimplePaginatedEntities($repository, $per_page);
	}

	public function getAllOrPaginatedEntities(Repository $repository, int $per_page = null)
	{
		return parent::getAllOrPaginatedEntities($repository, $per_page);
	}

	public function getPaginatedEntities(Repository $repository, int $per_page): PaginatorContract
	{
		return parent::getPaginatedEntities($repository, $per_page);
	}

	public function getEntities(Repository $repository): Collection
	{
		return parent::getEntities($repository);
	}

	public function getEntity(Repository $repository, $id): Model
	{
		return parent::getEntity($repository, $id);
	}

	public function createEntity(Repository $repository): Model
	{
		return parent::createEntity($repository);
	}

	public function updateEntity(Repository $repository, $id): Model
	{
		return parent::updateEntity($repository, $id);
	}

	public function updateManyEntities(Repository $repository): Collection
	{
		return parent::updateManyEntities($repository);
	}

	public function createManyEntities(Repository $repository): Collection
	{
		return parent::createManyEntities($repository);
	}

	public function deleteEntity(Repository $repository, $id): bool
	{
		return parent::deleteEntity($repository, $id);
	}
}
