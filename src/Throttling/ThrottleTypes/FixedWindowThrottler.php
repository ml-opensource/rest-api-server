<?php

namespace Fuzz\ApiServer\Throttling\ThrottleTypes;

use Carbon\Carbon;
use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Fuzz\HttpException\TooManyRequestsHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Predis\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FixedWindowThrottler
 *
 * FixedWindowThrottler restricts processes to a certain number within the specified period. For example,
 * $max_attempts = 3, $decay_time_seconds = 60 means "3 requests in a 60 second window".
 *
 * Windows are fixed in time meaning that you can still send 3 requests at the end of a window and then immediately
 * send another 3 requests at the start of the next window, effectively doubling the capacity in any one rolling
 * minute.
 *
 * FixedWindowThrottling is useful in cases which cannot be resolved to a rate per second so you can't use
 * the TokenBucketThrottler. For example, where you want to throttle login attempts to mitigate brute force
 * attacks.
 *
 * @package Fuzz\ApiServer\Throttling\ThrottleTypes
 */
abstract class FixedWindowThrottler implements Throttler
{
	/**
	 * Glue for rate limit keys
	 *
	 * @const string
	 */
	const GLUE          = ':';
	const KEY_PREFIX    = 'throttle:';
	const THROTTLE_TYPE = 'fixed_window';
	const ERROR_KEY     = 'too_many_requests';

	/**
	 * Key which is being rate limited
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Max attempts per decay time
	 *
	 * @var int
	 */
	protected $max_attempts = 3;

	/**
	 * Decay in minutes
	 *
	 * @var int
	 */
	protected $decay_time_seconds = 1;

	/**
	 * Number of attempts left within this decay period
	 *
	 * @var int
	 */
	protected $attempts_left;

	/**
	 * FixedWindowThrottler constructor.
	 *
	 * @param string $key
	 * @param int    $max_attempts
	 * @param int    $decay_time_seconds
	 */
	public function __construct(string $key, int $max_attempts, int $decay_time_seconds)
	{
		$this->key                = $this->makeKey($key);
		$this->max_attempts       = $max_attempts;
		$this->decay_time_seconds = $decay_time_seconds;
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
		return sprintf("%s:%s:%s", self::KEY_PREFIX, $this->getThrottleType(), $key);
	}

	/**
	 * Get a string identifying the throttle type
	 *
	 * @return string
	 */
	public function getThrottleType(): string
	{
		return self::THROTTLE_TYPE;
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
		$throttler = new static($key, $rate + $burst_allowance, $time_period_seconds);

		if ($throttler->isAtLimit()) {
			$headers = $throttler->getHeaders($throttler->getMaxAttempts(), $throttler->getAttemptsLeft(), $throttler->getDecaySeconds());
			throw new TooManyRequestsHttpException($throttler->getDecaySeconds(), 'Too Many Requests.', [], self::ERROR_KEY, self::ERROR_KEY, $headers);
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

		if ($this->getAttemptsLeft() <= 0) {
			return true;
		}

		$this->increment();

		return false;
	}

	/**
	 * Get how many attempts are left for this rate limit
	 *
	 * @return int
	 */
	public function getAttemptsLeft(): int
	{
		$attempts = Redis::get($this->key);

		if (is_null($attempts)) {
			$decay = $this->getDecaySeconds();
			$key   = $this->key;

			// Initialize the key and set it's expiration
			Redis::pipeline(function (Pipeline $pipe) use ($key, $decay) {
				$pipe->set($key, 0);
				$pipe->expire($key, $decay);
			});

			return $this->attempts_left = $this->getMaxAttempts();
		}

		return $this->attempts_left = $this->getMaxAttempts() - $attempts;
	}

	/**
	 * Get time before decay of key
	 *
	 * @return int
	 */
	public function getDecaySeconds(): int
	{
		return $this->decay_time_seconds;
	}

	/**
	 * Get the maximum number of attempts allowed per decay time
	 *
	 * @return int
	 */
	public function getMaxAttempts(): int
	{
		return $this->max_attempts;
	}

	/**
	 * Store the rate limit
	 *
	 * @return int
	 */
	public function increment(): int
	{
		$attempts = Redis::incr($this->key);

		// Key was newly created and incremented, we should make sure its expiry is set
		if ($attempts === 1) {
			Redis::expire($this->key, $this->getDecaySeconds());
		}

		$this->attempts_left = $this->getMaxAttempts() - $attempts;

		return $attempts;
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
			$headers['X-RateLimit-Reset'] = Carbon::now()
												->getTimestamp() + $retry_after;
		}

		return $headers;
	}

	/**
	 * Get a "reached rate limit" response
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getErrorResponse(): Response
	{
		$response = new JsonResponse([
			'error'             => 'too_many_requests',
			'error_description' => 'Too Many Requests.',
		], 429);

		$retry_after = $this->getDecaySeconds();

		return $this->addHeaders($response, $this->getMaxAttempts(), $this->getAttemptsLeft(), $retry_after);
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
}