<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Logging\Middleware\ActionLoggerMiddleware;
use Fuzz\ApiServer\Logging\Provider\ActionLoggerServiceProvider;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;

class ActionLoggerMiddlewareTest extends AppTestCase
{
	protected function getPackageProviders($app)
	{
		return [ActionLoggerServiceProvider::class];
	}

	public function getEnvironmentSetUp($app)
	{
		parent::getEnvironmentSetUp($app);

		$app['config']->set('action_log', [
			'driver' => 'mysql',
			'enabled' => true,

			'mysql' => [
				'model_class' => \Fuzz\ApiServer\Logging\ActionLog::class,
			],
		]);
	}

	public function testItDoesNotTryToLogIfLoggingIsDisabled()
	{
		$request_mock  = Mockery::mock(Request::class);
		$response_mock = Mockery::mock(Response::class);
		$middleware = new ActionLoggerMiddlewareImplShouldLogHasAgent;

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(false);

		$this->assertFalse($middleware->terminate($request_mock, $response_mock));
	}

	public function testItDoesNotTryToLogIfShouldNotLogActions()
	{
		$request_mock  = Mockery::mock(Request::class);
		$response_mock = Mockery::mock(Response::class);
		$middleware = new ActionLoggerMiddlewareImplShouldNotLog;

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(true);

		$this->assertFalse($middleware->terminate($request_mock, $response_mock));
	}

	public function testItDoesNotSetActionAgentIfNotHasActionAgent()
	{
		$request_mock  = Mockery::mock(Request::class);
		$response_mock = Mockery::mock(Response::class);
		$middleware = new ActionLoggerMiddlewareImplShouldLogDoesNotHaveAgent;

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(true);
		ActionLogger::shouldReceive('setActionAgentId')->never();
		ActionLogger::shouldReceive('setClientId')->with('someClientId')->once();
		ActionLogger::shouldReceive('flushQueue')->once()->andReturn(true);

		$this->assertTrue($middleware->terminate($request_mock, $response_mock));
	}

	public function testItDoesNotSetsActionAgentIfHasActionAgent()
	{
		$request_mock  = Mockery::mock(Request::class);
		$response_mock = Mockery::mock(Response::class);
		$middleware = new ActionLoggerMiddlewareImplShouldLogHasAgent;

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(true);
		ActionLogger::shouldReceive('setActionAgentId')->with('someAgentId')->once();
		ActionLogger::shouldReceive('setClientId')->with('someClientId')->once();
		ActionLogger::shouldReceive('flushQueue')->once()->andReturn(true);

		$this->assertTrue($middleware->terminate($request_mock, $response_mock));
	}

	public function testItDoesNotSetActionClientIfNotHasActionClient()
	{
		$request_mock  = Mockery::mock(Request::class);
		$response_mock = Mockery::mock(Response::class);
		$middleware = new ActionLoggerMiddlewareImplDoesNotHaveClient;

		ActionLogger::shouldReceive('isEnabled')->once()->andReturn(true);
		ActionLogger::shouldReceive('setActionAgentId')->with('someAgentId')->once();
		ActionLogger::shouldReceive('setClientId')->never();
		ActionLogger::shouldReceive('flushQueue')->once()->andReturn(true);

		$this->assertTrue($middleware->terminate($request_mock, $response_mock));
	}
}

class ActionLoggerMiddlewareImplShouldLogHasAgent extends ActionLoggerMiddleware
{
	/**
	 * Determine if we should log any actions
	 *
	 * @return bool
	 */
	public function shouldLogActions(): bool
	{
		return true;
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionAgent(): bool
	{
		return true;
	}

	/**
	 * Retrieve the current action agent's ID
	 *
	 * @return string
	 */
	public function getActionAgentId(): string
	{
		return 'someAgentId';
	}

	/**
	 * Retrieve the current client's ID
	 *
	 * @return string
	 */
	public function getActionClientId(): string
	{
		return 'someClientId';
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionClient(): bool
	{
		return true;
	}
}

class ActionLoggerMiddlewareImplDoesNotHaveClient extends ActionLoggerMiddleware
{
	/**
	 * Determine if we should log any actions
	 *
	 * @return bool
	 */
	public function shouldLogActions(): bool
	{
		return true;
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionAgent(): bool
	{
		return true;
	}

	/**
	 * Retrieve the current action agent's ID
	 *
	 * @return string
	 */
	public function getActionAgentId(): string
	{
		return 'someAgentId';
	}

	/**
	 * Retrieve the current client's ID
	 *
	 * @return string
	 */
	public function getActionClientId(): string
	{
		throw new \LogicException('Should not be called.');
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionClient(): bool
	{
		return false;
	}
}

class ActionLoggerMiddlewareImplShouldLogDoesNotHaveAgent extends ActionLoggerMiddleware
{
	/**
	 * Determine if we should log any actions
	 *
	 * @return bool
	 */
	public function shouldLogActions(): bool
	{
		return true;
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionAgent(): bool
	{
		return false;
	}

	/**
	 * Retrieve the current action agent's ID
	 *
	 * @return string
	 */
	public function getActionAgentId(): string
	{
		throw new \LogicException('Should not be called.');
	}

	/**
	 * Retrieve the current client's ID
	 *
	 * @return string
	 */
	public function getActionClientId(): string
	{
		return 'someClientId';
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionClient(): bool
	{
		return true;
	}
}

class ActionLoggerMiddlewareImplShouldNotLog extends ActionLoggerMiddleware
{
	/**
	 * Determine if we should log any actions
	 *
	 * @return bool
	 */
	public function shouldLogActions(): bool
	{
		return false;
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionAgent(): bool
	{
		throw new \LogicException('Should not be called.');
	}

	/**
	 * Retrieve the current action agent's ID
	 *
	 * @return string
	 */
	public function getActionAgentId(): string
	{
		throw new \LogicException('Should not be called.');
	}

	/**
	 * Retrieve the current client's ID
	 *
	 * @return string
	 */
	public function getActionClientId(): string
	{
		throw new \LogicException('Should not be called.');
	}

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	public function hasActionClient(): bool
	{
		throw new \LogicException('Should not be called.');
	}
}
