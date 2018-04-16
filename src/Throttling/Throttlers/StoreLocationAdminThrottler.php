<?php

namespace Fuzz\ApiServer\Throttling\Throttlers;

use Fuzz\ApiServer\Throttling\ThrottleTypes\FixedWindowThrottler;

/**
 * Class StoreLocationAdminThrottler
 *
 * Throttle by a store location ID
 *
 * @package Fuzz\ApiServer\Throttling
 */
class StoreLocationAdminThrottler extends FixedWindowThrottler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'store_location';

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