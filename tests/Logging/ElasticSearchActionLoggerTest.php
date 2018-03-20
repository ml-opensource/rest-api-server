<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Elasticsearch\Client;
use Fuzz\ApiServer\Logging\ActionLog;
use Fuzz\ApiServer\Logging\Contracts\ActionLogModel;
use Fuzz\ApiServer\Logging\ElasticSearchActionLogger;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery;

class ElasticSearchActionLoggerTest extends AppTestCase
{
	public function testItFlushesCacheToElasticSearch()
	{
		$request_mock = Mockery::mock(Request::class);
		$request_mock->shouldReceive('ip')->once()->andReturn('127.0.0.1');

		$es_mock = Mockery::mock(Client::class);

		$config = [
			'driver'  => 'aws-elasticsearch',
			'enabled' => true,
			'prefix' => 'foo_app',

			'elasticsearch' => [
				'region' => 'foo',
				// Only needed when using aws-elasticsearch provider.
				'config' => [
					'hosts' => [[],],
				],
			],
		];

		$logger = new ElasticSearchActionLogger($config, $request_mock, $es_mock);

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

		$es_mock->shouldReceive('index')->with(Mockery::on(function (array $data) {
			return $data['index'] === 'foo_app_action_log' && $data['type'] === 'action_log' && isset($data['id']);
		}))->times(8)->andReturn([]);

		$logger->flushQueue();

		$this->assertSame(0, $logger->getQueueLength());
	}

	public function testItReturnsFalseOnFlushIfQueueLengthIsZero()
	{
		$request_mock = Mockery::mock(Request::class);
		$request_mock->shouldReceive('ip')->once()->andReturn('127.0.0.1');

		$es_mock = Mockery::mock(Client::class);

		$config = [
			'driver'  => 'aws-elasticsearch',
			'enabled' => true,
			'prefix' => 'foo_app',

			'elasticsearch' => [
				'region' => 'foo',
				// Only needed when using aws-elasticsearch provider.
				'config' => [
					'hosts' => [[],],
				],
			],
		];

		$logger = new ElasticSearchActionLogger($config, $request_mock, $es_mock);

		$this->assertSame(0, $logger->getQueueLength());

		$this->assertFalse($logger->flushQueue());
	}
}