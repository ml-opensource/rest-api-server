<?php

namespace Fuzz\ApiServer\Throttling\Throttlers;

use Fuzz\ApiServer\Throttling\ThrottleTypes\TokenBucketThrottler;

/**
 * Class AccessTokenRequestRateThrottler
 *
 * Throttle access token req/sec
 *
 * @package Fuzz\ApiServer\Throttling
 */
class AccessTokenRequestRateThrottler extends TokenBucketThrottler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'access_token_req_sec';

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