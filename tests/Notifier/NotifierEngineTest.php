<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Notifier\BaseNotifier;
use Fuzz\ApiServer\Notifier\Contracts\Notifier;
use Fuzz\ApiServer\Notifier\NotifierEngine;
use Fuzz\ApiServer\Tests\AppTestCase;

class NotifierEngineTest extends AppTestCase
{
	public function testItCanNotifyAllHandlersInStackOfErrorAndPassesConfigAccordingly()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'error',
				],

				StubHandlerTwo::class => [
					'bar' => 'error',
				],
			],
		];

		$engine = new NotifierEngine($config);

		$this->assertTrue($engine->notifyError(new \Exception('foo')));
	}

	public function testNotifyErrorRequiresHandlerToBeNotifier()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'error',
				],

				StubHandlerTwo::class => [
					'bar' => 'error',
				],

				self::class => [],
			],
		];

		$engine = new NotifierEngine($config);

		$this->expectException(\LogicException::class);
		$engine->notifyError(new \Exception('foo'));
	}

	public function testItReturnsFalseIfNotAllHandlersNotifiedOfError()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'error',
				],

				StubHandlerTwo::class => [
					'bar' => 'error',
				],

				StubHandlerThree::class => [
					'baz' => 'error',
				],
			],
		];

		$engine = new NotifierEngine($config);

		$this->assertFalse($engine->notifyError(new \Exception('foo')));
	}

	public function testItCanNotifyAllHandlersInStackOfInfoAndPassesConfigAccordingly()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'info',
				],

				StubHandlerTwo::class => [
					'bar' => 'info',
				],
			],
		];

		$engine = new NotifierEngine($config);

		$this->assertTrue($engine->notifyInfo('some_event', ['bar' => 'baz']));
	}

	public function testItReturnsFalseIfNotAllHandlersNotifiedOfInfo()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'info',
				],

				StubHandlerTwo::class => [
					'bar' => 'info',
				],

				StubHandlerThree::class => [
					'baz' => 'info',
				],
			],
		];

		$engine = new NotifierEngine($config);

		$this->assertFalse($engine->notifyInfo('some_event', ['bar' => 'baz']));
	}

	public function testNotifyInfoRequiresHandlerToBeNotifier()
	{
		$config = [
			'handler_stack' => [
				StubHandlerOne::class => [
					'foo' => 'info',
				],

				StubHandlerTwo::class => [
					'bar' => 'info',
				],

				self::class => [],
			],
		];

		$engine = new NotifierEngine($config);

		$this->expectException(\LogicException::class);
		$engine->notifyInfo('foo', []);
	}
}

class StubHandlerOne extends BaseNotifier implements Notifier
{
	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if (! isset($this->config['foo']) || $this->config['foo'] !== 'error') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return true;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if (! isset($this->config['foo']) || $this->config['foo'] !== 'info') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return true;
	}
}

class StubHandlerTwo extends BaseNotifier implements Notifier
{
	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if (! isset($this->config['bar']) || $this->config['bar'] !== 'error') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return true;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if ($this->config['bar'] !== 'info') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return true;
	}
}

class StubHandlerThree extends BaseNotifier implements Notifier
{
	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $environment
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if (! isset($this->config['baz']) || $this->config['baz'] !== 'error') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return false;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param string $environment
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool
	{
		if ($environment !== 'testing') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Invalid environment.');
		}

		if ($this->config['baz'] !== 'info') {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed config expectation.');
		}

		return false;
	}
}