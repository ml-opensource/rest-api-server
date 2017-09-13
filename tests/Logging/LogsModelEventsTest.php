<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Logging\Provider\ActionLoggerServiceProvider;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Logging\Traits\LogsModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Mockery;

class LoggableActionsTest extends AppTestCase
{
	protected function getPackageProviders($app)
	{
		return [ActionLoggerServiceProvider::class];
	}

	public function setUp()
	{
		parent::setUp();

		$this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

		Schema::create('some_table', function (Blueprint $table) {
			$table->increments('id');
			$table->string('label');
			$table->timestamps();
		});
	}

	public function getEnvironmentSetUp($app)
	{
		parent::getEnvironmentSetUp($app);

		$app['config']->set('database.default', 'testbench');
		$app['config']->set(
			'database.connections.testbench', [
				'driver'   => 'sqlite',
				'database' => ':memory:',
				'prefix'   => ''
			]
		);

		$app['config']->set('services.action_logger', [
			'enabled' => true,
		]);
	}

	public function testItLogsModelCreatesToLogger()
	{
		$model = new StubModel;
		$model->label = 'bar';

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(true);
		ActionLogger::shouldReceive('log')->with('store', 'StubModel', Mockery::on(function ($arg) {
			$this->assertTrue(is_numeric($arg));
			return is_numeric($arg);
		}))->once();
		$model->save();
	}

	public function testItLogsModelUpdatesToLogger()
	{
		$model = new StubModel;
		$model->label = 'bar';

		ActionLogger::shouldReceive('isEnabled')->twice()->andReturn(true);
		ActionLogger::shouldReceive('log')->with('store', 'StubModel', Mockery::on(function ($arg) {
			$this->assertTrue(is_numeric($arg));
			return is_numeric($arg);
		}))->once();
		$model->save();

		$model->label = 'baz';

		ActionLogger::shouldReceive('log')->with('update', 'StubModel', $model->id)->once();
		$model->save();
	}

	public function testItLogsModelDeletesToLogger()
	{
		$model = new StubModel;
		$model->label = 'bar';

		ActionLogger::shouldReceive('isEnabled')->twice()->andReturn(true);
		ActionLogger::shouldReceive('log')->with('store', 'StubModel', Mockery::on(function ($arg) {
			$this->assertTrue(is_numeric($arg));
			return is_numeric($arg);
		}))->once();
		$model->save();

		$model->label = 'baz';

		ActionLogger::shouldReceive('log')->with('destroy', 'StubModel', $model->id)->once();
		$model->delete();
	}

	public function testItDoesNotLogIfLoggingIsDisabled()
	{
		$model = new StubModel;
		$model->label = 'bar';

		Auth::shouldReceive('user')->never();

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(false);
		ActionLogger::shouldReceive('log')->never();
		$model->save();

		$this->assertTrue($model->exists);
	}
}

class StubModel extends Model
{
	use LogsModelEvents;

	// So there's something to save to
	protected $table = 'some_table';
}
