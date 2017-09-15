<?php

namespace Fuzz\ApiServer\Throttling;

use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Fuzz\HttpException\TooManyRequestsHttpException;

/**
 * Class ArbitraryStringThrottler
 *
 * ArbitraryStringThrottler throttles by an arbitrary string key.
 *
 * @package Fuzz\ApiServer\Throttling
 */
class ArbitraryStringThrottler extends BaseThrottler implements Throttler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'arbitrary';

	/**
	 * Assert that the token throttle has not been reached
	 *
	 * @param string $string
	 * @param int    $max_attempts
	 * @param int    $decay_minutes
	 *
	 * @return array
	 */
	public static function assertThrottle(string $string, int $max_attempts, int $decay_minutes): array
	{
		$throttler = new self;
		$throttler->setMaxAttempts($max_attempts);
		$throttler->setDecay($decay_minutes);

		$key = $throttler->getKey([
			static::THROTTLE_TYPE,
			$string,
		]);

		if ($throttler->isAtLimit($key)) {
			$headers = $throttler->getHeaders($throttler->getMaxAttempts(), $throttler->getAttemptsLeft($key), $throttler->getDecaySeconds());
			throw new TooManyRequestsHttpException($throttler->getDecaySeconds(), 'Too Many Requests.', [], self::ERROR_KEY, self::ERROR_KEY, $headers);
		}

		$throttler->increment($key);

		return $throttler->getHeaders($throttler->getMaxAttempts(), $throttler->getAttemptsLeft($key));
	}
}