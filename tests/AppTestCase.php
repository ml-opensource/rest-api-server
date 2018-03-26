<?php

namespace Fuzz\ApiServer\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Maatwebsite\Excel\ExcelServiceProvider;
use Mockery;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class AppTestCase extends OrchestraTestCase
{
	protected $artisan;

	public function setUp()
	{
		parent::setUp();

		$this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
	}

	/**
	 * Tear down tests
	 *
	 * @throws \Throwable
	 */
	public function tearDown()
	{
		if (class_exists('Mockery')) {
			parent::verifyMockObjects();

			if ($container = Mockery::getContainer()) {
				$this->addToAssertionCount($container->mockery_getExpectationCount());
			}

			Mockery::close();
		}

		parent::tearDown();
	}

	/**
	 * Set the currently logged in user for the application.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable $user
	 * @param  string                                     $driver
	 *
	 * @return void
	 */
	public function be(Authenticatable $user, $driver = null)
	{
		throw new \RuntimeException('Should not be called.');
	}

	protected function getPackageProviders($app)
	{
		return [
			ConsoleServiceProvider::class,
			ExcelServiceProvider::class,
		];
	}
}
