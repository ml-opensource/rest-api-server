<?php

namespace Fuzz\ApiServer\Tests;

use Maatwebsite\Excel\ExcelServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class AppTestCase extends OrchestraTestCase
{
    protected $artisan;

    public function setUp()
    {
        parent::setUp();

        $this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
    }

    protected function getPackageProviders($app)
    {
        return [ExcelServiceProvider::class];
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
}
