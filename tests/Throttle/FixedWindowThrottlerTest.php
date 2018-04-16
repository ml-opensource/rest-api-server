<?php

namespace Fuzz\ApiServer\Tests\Throttle;

use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Throttling\ThrottleTypes\FixedWindowThrottler;
use Fuzz\HttpException\TooManyRequestsHttpException;

class FixedWindowThrottlerTest extends AppTestCase
{
	/**
	 * We expect to be able to make 10 req in every 2 second window.
	 *
	 * We send 10 requests and expect the 11th to be rejected.
	 *
	 * We wait two seconds to pass into the next fixed window and send 10 requests and expect the 11th to be rejected.
	 *
	 * We wait 1 second (still in same window) and expect the next request to be rejected.
	 */
	public function testFixedWindowThrottlerIsAtLimit()
	{
		$key = uniqid();

		$throttler = new FixedWindowThrottlerTestStub($key, 10, 2);

		for ($i = 0; $i < 10; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(2);

		for ($i = 0; $i < 10; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(1);

		$this->assertTrue($throttler->isAtLimit());
	}

	/**
	 * We expect to be able to send 10 requests in a 2 second window.
	 *
	 * We send 10 requests and expect the 11th to fail.
	 *
	 * We wait two seconds to pass into the next fixed window and send 10 requests and expect the 11th to fail.
	 *
	 * We wait 1 second (still in same window) and expect the next request to fail.
	 */
	public function testFixedWindowThrotttlerAssertThrottleWithNoBurst()
	{
		$key = uniqid();

		for ($i = 0; $i < 10; $i++) {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2);
		}

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(2);

		for ($i = 0; $i < 10; $i++) {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2);
		}

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(1);

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}
	}

	/**
	 * Same as testFixedWindowThrotttlerAssertThrottleWithNoBurst but burst adds a consisten +2 requests for a total
	 * of 12 requests per two seconds.
	 */
	public function testFixedWindowThrotttlerAssertThrottleWithBurst()
	{
		$key = uniqid();

		for ($i = 0; $i < 12; $i++) {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2, 2);
		}

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(2);

		for ($i = 0; $i < 12; $i++) {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2, 2);
		}

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(1);

		try {
			$headers = FixedWindowThrottlerTestStub::assertThrottle($key, 10, 2, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}
	}
}

class FixedWindowThrottlerTestStub extends FixedWindowThrottler
{
	/**
	 * Determine if this throttler is enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return true;
	}
}