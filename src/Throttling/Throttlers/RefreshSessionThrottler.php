<?php

namespace Fuzz\ApiServer\Throttling\Throttlers;

use Fuzz\ApiServer\Throttling\ThrottleTypes\FixedWindowThrottler;

/**
 * Class RefreshBySessionThrottler
 *
 * Throttler for session refreshes
 *
 * @package Fuzz\ApiServer\Throttling
 */
class RefreshSessionThrottler extends FixedWindowThrottler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'refresh_session';

	/**
	 * Determine if this throttler is enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return config('throttling.enabled', true);
	}
}