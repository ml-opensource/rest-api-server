<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Logging\ActionLog;
use Fuzz\ApiServer\Logging\Contracts\ActionLogModel;
use Fuzz\ApiServer\Logging\MySQLActionLogger;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * Class ElasticSearchActionLoggerTest
 *
 * @package Fuzz\ApiServer\Tests\Logging
 */
class MySQLActionLoggerTest extends AppTestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

		Schema::create('users', function (Blueprint $table) {
			$table->increments('id');
		});

		Schema::create('oauth_clients', function (Blueprint $table) {
			$table->string('id');
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
	}

	public function testItFlushesCacheToDatabase()
	{
		$request_mock  = Mockery::mock(Request::class);
		$request_mock->shouldReceive('ip')->once()->andReturn('127.0.0.1');

		$config = [
			'driver' => 'mysql',
			'enabled' => true,

			'mysql' => [
				'model_class' => \Fuzz\ApiServer\Logging\ActionLog::class,
			],
		];

		$logger = new MySQLActionLogger($config, $request_mock);

		$client = new LoggingTestOauthClient;
		$client->id = 'fooClientId';
		$client->save();

		$logger->setClientId('fooClientId');

		// Log 4 successes
		$logger->log('destroy', 'System', 7, 'someNote', ['foo' => 'bar']);
		$logger->log('store', 'Unit', 9, 'someNote', ['foo' => 'bar']);
		$logger->log('update', 'User', 10, 'someNote', ['foo' => 'bar']);
		$logger->log('destroy', 'Floor', 47, 'someNote', ['foo' => 'bar']);

		// Log 4 errors
		$logger->error('store', 'System', 22, 'not_found', 'someNote', ['foo' => 'bar']);
		$logger->error('update', 'System', 66, 'access_denied', 'someNote', ['foo' => 'bar']);
		$logger->error('destroy', 'System', 76, 'unknown_error', 'someNote', ['foo' => 'bar']);
		$logger->error('destroy', 'System', 75, 'not_found', 'someNote', ['foo' => 'bar']);

		$this->assertSame(8, $logger->getQueueLength());

		$queue = $logger->getMessageQueue();

		$logger->flushQueue();

		$stored_events = ActionLog::all();

		$this->assertSame(8, $stored_events->count());
		$this->assertSame(0, $logger->getQueueLength());

		foreach ($queue as $i => $message) {
			$record = $stored_events->get($i);

			$this->assertSame($message['client_id'], 'fooClientId');

			foreach ($message as $key => $value) {
				if ($key === 'meta') {
					$this->assertSame(json_decode($value, true), $record->{$key});
					continue;
				}

				if ($key === 'timestamp') {
					$this->assertSame($value, $record->{$key}->toDateTimeString());
					continue;
				}

				$this->assertSame($value, $record->{$key});
			}
		}
	}

	public function testItFlushesCacheToDatabaseWithCustomModel()
	{
		$request_mock  = Mockery::mock(Request::class);
		$request_mock->shouldReceive('ip')->once()->andReturn('127.0.0.1');

		$config = [
			'driver' => 'mysql',
			'enabled' => true,

			'mysql' => [
				'model_class' => SomeOtherLoggingModel::class,
			],
		];

		Schema::create('some_logging_table', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('user_id')->unsigned()->nullable();
			$table->string('client_id')->nullable();
			$table->string('resource')->nullable();
			$table->string('resource_id')->nullable();
			$table->string('action');
			$table->string('request_id')->nullable();
			$table->string('error_status')->nullable();
			$table->text('note')->nullable();
			$table->string('ip');
			$table->json('meta')->nullable();
			$table->timestamp('created_at')->nullable();

			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('client_id')->references('id')->on('oauth_clients');
		});

		$logger = new MySQLActionLogger($config, $request_mock);

		$client = new LoggingTestOauthClient;
		$client->id = 'fooClientId';
		$client->save();

		$logger->setClientId('fooClientId');

		// Log 4 successes
		$logger->log('destroy', 'System', 7, 'someNote', ['foo' => 'bar']);
		$logger->log('store', 'Unit', 9, 'someNote', ['foo' => 'bar']);
		$logger->log('update', 'User', 10, 'someNote', ['foo' => 'bar']);
		$logger->log('destroy', 'Floor', 47, 'someNote', ['foo' => 'bar']);

		// Log 4 errors
		$logger->error('store', 'System', 22, 'not_found', 'someNote', ['foo' => 'bar']);
		$logger->error('update', 'System', 66, 'access_denied', 'someNote', ['foo' => 'bar']);
		$logger->error('destroy', 'System', 76, 'unknown_error', 'someNote', ['foo' => 'bar']);
		$logger->error('destroy', 'System', 75, 'not_found', 'someNote', ['foo' => 'bar']);

		$this->assertSame(8, $logger->getQueueLength());

		$queue = $logger->getMessageQueue();

		$logger->flushQueue();

		$stored_events = SomeOtherLoggingModel::all();

		$this->assertSame(8, $stored_events->count());
		$this->assertSame(0, $logger->getQueueLength());

		foreach ($queue as $i => $message) {
			$record = $stored_events->get($i);

			$this->assertSame($message['client_id'], 'fooClientId');

			foreach ($message as $key => $value) {
				if ($key === 'meta') {
					$this->assertSame(json_decode($value, true), $record->{$key});
					continue;
				}

				if ($key === 'timestamp') {
					$this->assertSame($value, $record->{$key}->toDateTimeString());
					continue;
				}

				$this->assertSame($value, $record->{$key});
			}
		}
	}

	public function testItReturnsFalseOnFlushIfQueueLengthIsZero()
	{
		$request_mock  = Mockery::mock(Request::class);
		$request_mock->shouldReceive('ip')->once()->andReturn('127.0.0.1');

		$config = [
			'driver' => 'mysql',
			'enabled' => true,

			'mysql' => [
				'model_class' => \Fuzz\ApiServer\Logging\ActionLog::class,
			],
		];

		$logger = new MySQLActionLogger($config, $request_mock);

		$this->assertSame(0, $logger->getQueueLength());

		$this->assertFalse($logger->flushQueue());
	}
}

class SomeOtherLoggingModel extends Model implements ActionLogModel
{
	protected $table = 'some_logging_table';

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'id'           => 'integer',
		'created_at'   => 'datetime',
		'user_id'      => 'integer',
		'resource'     => 'string',
		'resource_id'  => 'string',
		'action'       => 'string',
		'note'         => 'string',
		'error_status' => 'string',
		'ip'           => 'string',
		'meta'         => 'array',
	];

	/**
	 * Ignore updated at
	 *
	 * @param  mixed $value
	 *
	 * @return $this
	 */
	public function setUpdatedAt($value)
	{
		return $this;
	}
}