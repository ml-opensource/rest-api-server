<?php

namespace Fuzz\ApiServer\Tests\Throttle;

use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Throttling\Provider\ThrottleServiceProvider;
use Fuzz\ApiServer\Throttling\ThrottleTypes\TokenBucketThrottler;
use Fuzz\HttpException\TooManyRequestsHttpException;
use Illuminate\Redis\RedisServiceProvider;

class TokenBucketThrottlerTest extends AppTestCase
{
	/**
	 * We pass the TokenBucketThrottler a rate of 3 req/second and a burst allowance of +3 req/sec for 6 req/sec
	 * total burst.
	 *
	 * For the first set of requests we send 6 requests which should all pass and use up the burst allowance. Request
	 * #7 should then be reject.
	 *
	 * We then wait (capacity/rate) * 2 or (6/3)*2=4 seconds for the burst allowance to be restored and send 6
	 * requests again and expect the 7th to be rejected.
	 *
	 * We then wait for 1 second, enough for the initial rate to be restored but not the burst rate. We send 3 requests
	 * to use up the base rate and we expect the 4th to be rejected.
	 *
	 * We then wait for the burst capacity to be restored and send 6 requests and expect the 7th to be rejected
	 */
	public function testTokenBucketThrottlerIsAtLimitWithBurst()
	{
		$key       = uniqid();
		$throttler = new TokenBucketThrottlerTestStub($key, 3, 3);

		for ($i = 0; $i < 6; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(4);

		for ($i = 0; $i < 6; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(1);

		for ($i = 0; $i < 3; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(4);

		for ($i = 0; $i < 6; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());
	}

	/**
	 * This is a test for the burst/rate ratio being an uneven number.
	 *
	 * We set up the Token Bucket Throttler with a rate of 14 with 14+5=19 burst req/sec.
	 *
	 * We send 19 burst requests and expect the 20th to be rejected.
	 *
	 * We then wait floor((capacity/rate)*2) or floor((19/14)*2)=2 seconds for the burst to be refilled and send
	 * 19 burst requests and expect the 20th to be rejected.
	 *
	 * We then wait 1 second to refill the base rate and send 14 requests and expect the 15th to be rejected.
	 *
	 * We then wait floor((capacity/rate)*2) or floor((19/14)*2)=2 seconds for the burst to be refilled and send
	 * 19 burst requests and expect the 20th to be rejected.
	 */
	public function testTokenBucketThrottlerTestStubIsAtLimitWithBurstDecimalRatio()
	{
		$key       = uniqid();
		$throttler = new TokenBucketThrottlerTestStub($key, 14, 5);

		for ($i = 0; $i < 19; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(2);

		for ($i = 0; $i < 19; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(1);

		for ($i = 0; $i < 14; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(4);

		for ($i = 0; $i < 19; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());
	}

	/**
	 * We create a TokenBucketThrottlerTestStub with a rate of 3 req/sec and no burst allowance.
	 *
	 * We send 3 requests and expect them to be accepted. The 4th should be rejected.
	 *
	 * We wait 2 seconds to refill the req rate and send another 3 and expect the 4th to be rejected.
	 *
	 * We wait 2 seconds to refill the req rate and send another 3 and expect the 4th to be rejected.
	 */
	public function testTokenBucketThrottleIsAtLimitWithNoBurst()
	{
		$key       = uniqid();
		$throttler = new TokenBucketThrottlerTestStub($key, 3, 0);

		for ($i = 0; $i < 3; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(2);

		for ($i = 0; $i < 3; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());

		sleep(2);

		for ($i = 0; $i < 3; $i++) {
			$this->assertFalse($throttler->isAtLimit());
		}
		$this->assertTrue($throttler->isAtLimit());
	}

	/**
	 * We set up the Token Bucket Throttler with a rate of 10 req per 5 seconds (2 req/sec) and a burst of (2+2=4)
	 * req/sec.
	 *
	 * We send 4 burst requests and expect the 5th to fail.
	 *
	 * We then wait 2 seconds to refill the burst rate and send another 4 requests and expect the 5th to fail.
	 *
	 * We then wait 1 second (lower than the burst refill) and expect to only be able to send 2 requests and the 3rd
	 * to fail.
	 */
	public function testTokenBucketThrottlerTestStubAssertThrottleWithBurst()
	{
		$key = uniqid();

		for ($i = 0; $i < 4; $i++) {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);
		}

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(2);

		for ($i = 0; $i < 4; $i++) {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);
		}

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		sleep(1);

		for ($i = 0; $i < 2; $i++) {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);
		}

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 10, 5, 2);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}
	}

	/**
	 * We set up the Token Bucket Throttler with a rate of 100 requests per 5 seconds (20 req/second).
	 *
	 * We send 20 requests in 1 second and expect them all to succeed. The 21st request should fail.
	 *
	 * We then sleep for 1 second to restore the rate and send another 20 requests in 1 second and expect them
	 * all to succeed. The 21st should fail.
	 *
	 * We then sleep for 0.1 seconds (less than the restore rate) and expect the request to fail.
	 */
	public function testTokenBucketThrottlerTestStubAssertThrottleWithNoBurst()
	{
		$key = uniqid();

		for ($i = 0; $i < 20; $i++) {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 100, 5);
		}

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 100, 5);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		// @todo only need to sleep 1 sec to refresh req rate. Update.
		sleep(1);

		for ($i = 0; $i < 20; $i++) {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 100, 5);
		}

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 100, 5);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}

		usleep(100000); // 0.1 seconds

		try {
			$headers = TokenBucketThrottlerTestStub::assertThrottle($key, 100, 5);

			$this->fail('Expected exception.');
		} catch (TooManyRequestsHttpException $e) {
			$this->assertSame(0, $headers['X-RateLimit-Remaining']);
		}
	}

	protected function getPackageProviders($app)
	{
		return array_merge([
			ThrottleServiceProvider::class,
			RedisServiceProvider::class,
		], parent::getPackageProviders($app));
	}
}

class TokenBucketThrottlerTestStub extends TokenBucketThrottler
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
