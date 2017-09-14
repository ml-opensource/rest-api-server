<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Logging\BaseActionLogger;
use Fuzz\ApiServer\Tests\TestCase;
use Illuminate\Http\Request;
use Mockery;

/**
 * Class BaseActionLoggerTest
 *
 * @package Fuzz\ApiServer\Tests\Logging
 */
class BaseActionLoggerTest extends TestCase
{
	public function testItSetsLoggingEnabled()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->twice()->andReturn('52.34.56.12');
		$config = [
			'enabled' => false,
		];

		$logger = new TestLoggerImplementation($config, $request);
		$this->assertFalse($logger->isEnabled());

		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);
		$this->assertTrue($logger->isEnabled());
	}

	public function testItCanLogActions()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => null,
			'client_id'    => null,
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);
	}

	public function testItWritesAgentIdToNewActions()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->setActionAgentId('678');

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => '678',
			'client_id'    => null,
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);
	}

	public function testItWritesClientIdToNewActions()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->setClientId('678');

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => null,
			'client_id'    => '678',
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);
	}

	public function testItRetroactivelyAppliesAgentIdToActions()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => null,
			'client_id'    => null,
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);

		$logger->setActionAgentId('890');

		$this->assertSame('890', $logger->getMessageQueue()[0]['user_id']);
	}

	public function testItRetroactivelyAppliesClientIdToActions()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => null,
			'client_id'    => null,
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);

		$logger->setClientId('890');

		$this->assertSame('890', $logger->getMessageQueue()[0]['client_id']);
	}

	public function testItCanClearTheQueue()
	{
		$request = Mockery::mock(Request::class);
		$request->shouldReceive('ip')->once()->andReturn('52.34.56.12');
		$config = [
			'enabled' => true,
		];

		$logger = new TestLoggerImplementation($config, $request);

		$logger->log('someAction', 'someResource', 'someResourceId', 'someNote', ['foo' => 'bar']);

		$this->assertSame(1, $logger->getQueueLength());
		$this->assertSame([
			'user_id'      => null,
			'client_id'    => null,
			'resource'     => 'someResource',
			'resource_id'  => 'someResourceId',
			'action'       => 'someAction',
			'ip'           => '52.34.56.12',
			'meta'         => json_encode(['foo' => 'bar']),
			'note'         => 'someNote',
			'error_status' => null,
		], $logger->getMessageQueue()[0]);

		$logger->clearQueue();

		$this->assertSame(0, $logger->getQueueLength());
	}
}

class TestLoggerImplementation extends BaseActionLogger
{
	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	public function flushQueue(): bool
	{
		throw new \LogicException('Should not be called.');
	}
}