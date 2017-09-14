<?php

namespace Fuzz\ApiServer\Throttling;

use Closure;
use Fuzz\ApiServer\Throttling\Contracts\Throttler;
use Illuminate\Http\Request;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TokenThrottler
 *
 * TokenThrottler throttles by a token string.
 *
 * @package Fuzz\ApiServer\Throttling
 */
class TokenThrottler extends ArbitraryStringThrottler implements Throttler
{
	/**
	 * Throttle type key
	 *
	 * @const string
	 */
	const THROTTLE_TYPE = 'token';

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
	public function handle(Request $request, Closure $next, int $max_attempts = 5, int $decay_minutes = 1): Response
	{
		$key = implode(':', [
			$request->getRequestUri(),
			$request->method(),
			Authorizer::getAccessToken(), // Has a __toString()
		]);

		$headers = self::assertThrottle($key, $max_attempts, $decay_minutes);

		$response = $next($request);

		$response->headers->add($headers);

		return $response;
	}
}