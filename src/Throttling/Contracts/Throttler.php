<?php

namespace Fuzz\ApiServer\Throttling\Contracts;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface Throttler
 *
 * A Throttler is responsible for handling it's own specialized type of throttling (IP, Token, SystemID, etc).
 */
interface Throttler
{
	/**
	 * Get the key used to identify a throttle restriction
	 *
	 * i.e. implode(':', $attributes)
	 *
	 * @param array  $parts
	 *
	 * @return string
	 */
	public function getKey(array $parts): string;

	/**
	 * Store the rate limit
	 *
	 * @param string $key
	 *
	 * @return int
	 */
	public function increment(string $key): int;

	/**
	 * Determine if this request is at the rate limit
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isAtLimit(string $key): bool;

	/**
	 * Get how many attempts are left for this rate limit
	 *
	 * @param string $key
	 *
	 * @return int
	 */
	public function getAttemptsLeft(string $key): int;

	/**
	 * Get the maximum number of attempts allowed per decay time
	 *
	 * @return int
	 */
	public function getMaxAttempts(): int;

	/**
	 * Set the maximum number of attempts allowed per decay time
	 * 
	 * @param int $max_attempts
	 *
	 * @return \Fuzz\ApiServer\Throttling\Contracts\Throttler
	 */
	public function setMaxAttempts(int $max_attempts): Throttler;

	/**
	 * Get the decay limit in minutes
	 *
	 * @return int
	 */
	public function getDecayMinutes(): int;

	/**
	 * Get the decay limit in seconds
	 *
	 * @return int
	 */
	public function getDecaySeconds(): int;

	/**
	 * Set the decay time for this rate limit
	 *
	 * @param int $decay_minutes
	 *
	 * @return \Fuzz\ApiServer\Throttling\Contracts\Throttler
	 */
	public function setDecay(int $decay_minutes): Throttler;

	/**
	 * Get a "reached rate limit" response
	 *
	 * @param string $key
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse(string $key): Response;
}