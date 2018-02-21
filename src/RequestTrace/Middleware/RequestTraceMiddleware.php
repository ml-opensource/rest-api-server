<?php

namespace Fuzz\ApiServer\RequestTrace\Middleware;

use Closure;
use Fuzz\ApiServer\RequestTrace\Facades\RequestTracer;
use Fuzz\ApiServer\RequestTrace\RequestId;
use Fuzz\ApiServer\Utility\UUID;
use Illuminate\Http\Request;

class RequestTraceMiddleware
{
	/**
	 * Header names
	 *
	 * @const string
	 */
	const ELB_TRACE_ID      = 'X-Amzn-Trace-Id';
	const REQUEST_ID_HEADER = 'X-Request-Id';

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 *
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$request_id = null;

		if ($request->headers->has(self::ELB_TRACE_ID)) {
			$request_id = $request->header(self::ELB_TRACE_ID);
		} else {
			$request_id = UUID::generate();
		}

		app()->singleton(RequestTracer::class, function () use ($request_id) {
			return new RequestId($request_id);
		});

		/** @var \Symfony\Component\HttpFoundation\Response $response */
		$response = $next($request);

		$response->headers->set(self::REQUEST_ID_HEADER, $request_id);

		return $response;
	}
}
