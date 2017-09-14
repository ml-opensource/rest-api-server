<?php

namespace Fuzz\ApiServer\Throttling;

use Closure;
use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IPThrottler
 *
 * IPThrottler throttles by IP.
 *
 * @package Fuzz\ApiServer\Throttling
 */
class IPThrottler extends ArbitraryStringThrottler implements Throttler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'ip';

	/**
	 * Throttle a request
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure                 $next
	 * @param int                      $max_attempts
	 * @param int                      $decay_minutes
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle(Request $request, Closure $next, int $max_attempts = 60, int $decay_minutes = 1): Response
	{
		$key = implode(':', [
			$request->ip(),
		]);

		$headers = self::assertThrottle($key, $max_attempts, $decay_minutes);

		$response = $next($request);

		$response->headers->add($headers);

		return $response;
	}
}