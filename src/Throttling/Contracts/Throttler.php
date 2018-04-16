<?php

namespace Fuzz\ApiServer\Throttling\Contracts;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface Throttler
 *
 * A Throttler is responsible for handling it's own specialized type of throttling (IP, String, etc).
 */
interface Throttler
{
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
	public static function assertThrottle(string $key, int $rate, int $time_period_seconds, int $burst_allowance = 0): array;

	/**
	 * Determine if this throttler is enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool;

	/**
	 * Determine if this key is at the rate limit
	 *
	 * @return bool
	 */
	public function isAtLimit(): bool;

	/**
	 * Get how many attempts are left for this rate limit
	 *
	 * @return int
	 */
	public function getAttemptsLeft(): int;

	/**
	 * Get the maximum number of attempts allowed per decay time
	 *
	 * @return int
	 */
	public function getMaxAttempts(): int;

	/**
	 * Get a "reached rate limit" response
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getErrorResponse(): Response;

	/**
	 * Get a string identifying the throttle type
	 *
	 * @return string
	 */
	public function getThrottleType(): string;
}