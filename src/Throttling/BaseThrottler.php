<?php

namespace Fuzz\ApiServer\Throttling;

use Carbon\Carbon;
use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseRedisThrottler
 *
 * BaseRedisThrottler is the base throttler which is backed by Redis and provides throttling functionality.
 *
 * @package Fuzz\ApiServer\Throttling
 */
abstract class BaseThrottler implements Throttler
{
	/**
	 * Glue for rate limit keys
	 *
	 * @const string
	 */
	const GLUE = ':';
	const KEY_PREFIX = 'throttle:';
	const ERROR_KEY = 'too_many_requests';

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
	protected $decay_minutes = 1;

	/**
	 * Number of attempts left within this decay period
	 *
	 * @var int
	 */
	protected $attempts_left;

	/**
	 * Determine if this request is at the rate limit
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isAtLimit(string $key): bool
	{
		if (! config('services.rate_limit.enabled', true)) {
			return false;
		}

		return $this->getAttemptsLeft($key) <= 0;
	}

	/**
	 * Get how many attempts are left for this rate limit
	 *
	 * @param string $key
	 *
	 * @return int
	 */
	public function getAttemptsLeft(string $key): int
	{
		if (! is_null($this->attempts_left)) {
			return $this->attempts_left;
		}

		$attempts = Cache::get($key);

		if (is_null($attempts)) {
			Cache::put($key, 0, $this->getDecayMinutes());

			return $this->attempts_left = $this->getMaxAttempts();
		}

		return $this->attempts_left = $this->getMaxAttempts() - $attempts;
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
	 * @param int $max_attempts
	 *
	 * @return \Fuzz\ApiServer\Throttling\Contracts\Throttler
	 */
	public function setMaxAttempts(int $max_attempts): Throttler
	{
		$this->max_attempts = $max_attempts;

		return $this;
	}

	/**
	 * Set the decay time for this rate limit
	 *
	 * @param int $decay_minutes
	 *
	 * @return \Fuzz\ApiServer\Throttling\Contracts\Throttler
	 */
	public function setDecay(int $decay_minutes): Throttler
	{
		$this->decay_minutes = $decay_minutes;

		return $this;
	}

	/**
	 * Store the rate limit
	 *
	 * @param string $key
	 *
	 * @return int
	 */
	public function increment(string $key): int
	{
		$attempts = Cache::increment($key);

		// Key was newly created and incremented, we should make sure its expiry is set
		if ($attempts === 1) {
			Cache::put($key, 1, $this->getDecayMinutes());
		}

		$this->attempts_left = $this->getMaxAttempts() - $attempts;

		return $attempts;
	}

	/**
	 * Get the decay limit
	 *
	 * @return int
	 */
	public function getDecaySeconds(): int
	{
		return $this->getDecayMinutes() * 60;
	}

	/**
	 * Get the decay limit
	 *
	 * @return int
	 */
	public function getDecayMinutes(): int
	{
		return $this->decay_minutes;
	}

	/**
	 * Get a "reached rate limit" response
	 *
	 * @param string $key
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse(string $key): Response
	{
		$response = new JsonResponse([
			'error'             => 'too_many_requests',
			'error_description' => 'Too Many Requests.',
		], 429);

		$retry_after = $this->getDecaySeconds();

		return $this->addHeaders($response, $this->getMaxAttempts(), $this->getAttemptsLeft($key), $retry_after);
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
	 * Get the key used to identify a throttle restriction
	 *
	 * i.e. implode(':', $attributes)
	 *
	 * @param array  $parts
	 *
	 * @return string
	 */
	public function getKey(array $parts): string
	{
		return self::KEY_PREFIX . hash('sha256', implode(self::GLUE, $parts));
	}
}