<?php

namespace Fuzz\ApiServer\Throttling\ThrottleTypes;

use Carbon\Carbon;
use Fuzz\ApiServer\Throttling\Commands\TokenBucketCommand;
use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Fuzz\HttpException\TooManyRequestsHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TokenBucketThrottler
 *
 * A Throttler that uses the Token Bucket algorithm to control the rate of process access (ex: requests).
 *
 * Note: The limitation of this algorithm is that rates can only be throttled to a resolution of req per second and
 * not to a larger interval of time (minute, hour, etc). Another way of saying this is if your rate per second is
 * greater than 1, this throttling strategy will work. If your rate per second is less than one, this will not.
 *
 * TokenBucketThrottler in cases where you want to throttle evenly over a rolling period of time such as requests
 * per API Token per second (or minute) and can resolve your rate to a rate per second.
 *
 * @see     \Fuzz\ApiServer\Throttling\Commands\TokenBucketCommand@getScript()
 * @see     https://en.wikipedia.org/wiki/Token_bucket
 *
 * @package Fuzz\ApiServer\Throttling\ThrottleTypes
 */
abstract class TokenBucketThrottler implements Throttler
{
	/**
	 * Glue for rate limit keys
	 *
	 * @const string
	 */
	const GLUE          = ':';
	const KEY_PREFIX    = 'throttle:';
	const THROTTLE_TYPE = 'token_bucket';
	const ERROR_KEY     = 'too_many_requests';

	/**
	 * Key which is being rate limited
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Refill rate for key
	 *
	 * @var int
	 */
	protected $rate;

	/**
	 * Allowance for burst req
	 *
	 * @var int
	 */
	protected $burst_allowance;

	/**
	 * Is this process allowed to occur?
	 *
	 * @var bool
	 */
	protected $allowed = true;

	/**
	 * Remaining tokens in bucket
	 *
	 * @var int
	 */
	protected $remaining;

	/**
	 * TokenBucketThrottler constructor.
	 *
	 * @param string $key
	 * @param int    $rate
	 * @param int    $burst_allowance
	 */
	public function __construct(string $key, int $rate = 10, int $burst_allowance = 5)
	{
		$this->key             = $this->makeKey($key);
		$this->rate            = $rate;
		$this->remaining       = $rate;
		$this->burst_allowance = $rate + $burst_allowance;

		Redis::getProfile()
			 ->defineCommand('tokenBucket', TokenBucketCommand::class);
	}

	/**
	 * Get a string identifying the throttle type
	 *
	 * @return string
	 */
	public function getThrottleType(): string
	{
		return static::THROTTLE_TYPE;
	}

	/**
	 * Get a "reached rate limit" response
	 *
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getErrorResponse(): Response
	{
		$response = new JsonResponse([
			'error'             => 'too_many_requests',
			'error_description' => 'Too Many Requests.',
		], 429);

		return $this->addHeaders($response, $this->getMaxAttempts(), $this->getAttemptsLeft(), $this->getDecaySeconds());
	}

	/**
	 * Add the limit header information to the given response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response $response
	 * @param  int                                        $max_attempts
	 * @param  int                                        $remaining_attempts
	 * @param  int|null                                   $retry_after
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function addHeaders(Response $response, int $max_attempts, int $remaining_attempts, int $retry_after = null): Response
	{
		$headers = $this->getHeaders($max_attempts, $remaining_attempts, $retry_after);

		$response->headers->add($headers);

		return $response;
	}

	/**
	 * Create the headers array for a response
	 *
	 * @param int      $max_attempts
	 * @param int      $remaining_attempts
	 * @param int|null $retry_after
	 *
	 * @return array
	 */
	public function getHeaders(int $max_attempts, int $remaining_attempts, int $retry_after = null): array
	{
		$headers = [
			'X-RateLimit-Limit'     => $max_attempts,
			'X-RateLimit-Remaining' => $remaining_attempts,
		];

		if (! is_null($retry_after)) {
			$headers['Retry-After']       = $retry_after;
			$headers['X-RateLimit-Reset'] = Carbon::now()->getTimestamp() + $retry_after;
		}

		return $headers;
	}

	/**
	 * Get the maximum number of attempts allowed per decay time
	 *
	 * @return int
	 */
	public function getMaxAttempts(): int
	{
		return $this->rate;
	}

	/**
	 * Get how many attempts are left for this rate limit
	 *
	 * @return int
	 */
	public function getAttemptsLeft(): int
	{
		return $this->remaining;
	}

	/**
	 * Get time before decay of key
	 *
	 * @return int
	 */
	public function getDecaySeconds(): int
	{
		return floor(($this->burst_allowance / $this->rate) * 2);
	}

	/**
	 * Assert that the token throttle has not been reached, should return an array of rate limit headers
	 *
	 * @param string $key
	 * @param int    $rate
	 * @param int    $time_period_seconds
	 * @param int    $burst_allowance
	 *
	 * @return array
	 */
	public static function assertThrottle(string $key, int $rate, int $time_period_seconds, int $burst_allowance = 0): array
	{
		$rate_per_second = floor($rate / $time_period_seconds);

		if ($rate_per_second < 1) {
			throw new \LogicException('Unsupported rate and time period supplied to ' . static::class . '.');
		}

		$throttler = new static($key, $rate_per_second, $burst_allowance);

		if ($throttler->isAtLimit()) {
			$headers = $throttler->getHeaders($throttler->getMaxAttempts(), $throttler->getAttemptsLeft(), $throttler->getDecaySeconds());
			throw new TooManyRequestsHttpException($throttler->getDecaySeconds(), 'Too Many Requests.', [], static::ERROR_KEY, static::ERROR_KEY, $headers);
		}

		return $throttler->getHeaders($throttler->getMaxAttempts(), $throttler->getAttemptsLeft());
	}

	/**
	 * Determine if this key is at the rate limit
	 *
	 * @return bool
	 */
	public function isAtLimit(): bool
	{
		if (! $this->isEnabled()) {
			return false;
		}

		$tokens    = $this->key . '.tokens';
		$timestamp = $this->key . '.timestamp';

		list($allowed, $this->remaining) = Redis::tokenBucket($tokens, $timestamp, $this->rate, $this->burst_allowance, time(), 1);
		$this->allowed = (bool) $allowed;

		return ! $this->allowed;
	}

	/**
	 * Generate the complete cache key for a given base key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function makeKey(string $key): string
	{
		return sprintf("%s:%s:%s", static::KEY_PREFIX, $this->getThrottleType(), $key);
	}
}