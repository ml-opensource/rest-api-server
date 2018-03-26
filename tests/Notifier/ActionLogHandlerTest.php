<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Logging\ActionLog;
use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Notifier\Handlers\ActionLog\ActionLogHandler;
use Fuzz\ApiServer\Tests\AppTestCase;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;
use Illuminate\Http\Request as ConcreteRequest;

class ActionLogHandlerTest extends AppTestCase
{
	public function testItDoesNotNotifyErrorIfActionLoggerIsNotBound()
	{
		$config = [];

		$handler = new ActionLogHandler($config);

		$this->assertFalse($this->app->bound(ActionLogger::class));
		$this->assertFalse($handler->notifyError(new \Exception, 'production'));
	}

	public function testItNotifiesError()
	{
		$config = [];

		$handler = new ActionLogHandlerStub($config);

		$logger_mock = Mockery::mock(ActionLog::class);
		// Pretend that the action logger is bound
		$this->app->singleton(ActionLogger::class, function () use ($logger_mock) {
			return $logger_mock;
		});

		$exception = new \Exception('Foo');

		$logger_mock->shouldReceive('error')->with('notified_error_event', 'cli', null, get_class($exception), null, [
			'php_sapi'        => 'cli',
		]);

		$this->assertTrue($handler->notifyError($exception, 'production'));
	}

	public function testItDoesNotNotifyInfoIfActionLoggerIsNotBound()
	{
		$config = [];

		$handler = new ActionLogHandler($config);

		$this->assertFalse($this->app->bound(ActionLogger::class));
		$this->assertFalse($handler->notifyInfo('fooEvent', 'production'));
	}

	public function testItNotifiesInfo()
	{
		$config = [];

		$handler = new ActionLogHandlerStub($config);

		$logger_mock = Mockery::mock(ActionLog::class);
		// Pretend that the action logger is bound
		$this->app->singleton(ActionLogger::class, function () use ($logger_mock) {
			return $logger_mock;
		});

		$logger_mock->shouldReceive('log')->with('FooEvent', null, null, null, null, [
			'bar' => 'bat',
		]);

		$this->assertTrue($handler->notifyInfo('FooEvent', 'production', ['bar' => 'bat']));
	}
}

class ActionLogHandlerStub extends ActionLogHandler
{
	/**
	 * Get the concrete request
	 *
	 * @return \Illuminate\Http\Request
	 */
	protected function getRequest(): ConcreteRequest
	{
		$request_mock = Mockery::mock(ConcreteRequest::class);
		$header_mock = Mockery::mock(HeaderBag::class);
		$request_mock->headers = $header_mock;

		return $request_mock;
	}
}