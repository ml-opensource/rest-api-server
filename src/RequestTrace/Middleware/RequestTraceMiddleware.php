<?php

namespace Fuzz\ApiServer\RequestTrace\Middleware;

use Closure;
use Fuzz\ApiServer\RequestTrace\Facades\RequestTracer;
use Fuzz\ApiServer\RequestTrace\RequestId;
use Fuzz\ApiServer\Utility\UUID;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

class RequestTraceMiddleware
{
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
		$request_id = $this->getRequestId($request);

		app()->singleton(RequestTracer::class, function () use ($request_id) {
			return new RequestId($request_id);
		});

		/** @var \Symfony\Component\HttpFoundation\Response $response */
		$response = $next($request);

		$response->headers->set(RequestTracer::REQUEST_ID_HEADER, $request_id);

		return $response;
	}

	/**
	 * Find the Request ID or generat it
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return string
	 */
	protected function getRequestId(Request $request): string
	{
		$request_id = null;
		$headers = $request->headers;

		// Is from ELB
		if ($headers->has(RequestTracer::ELB_TRACE_ID)) {
			$request_id = $request->header(RequestTracer::ELB_TRACE_ID);
		} elseif ($headers->has(RequestTracer::FUZZ_TRACE_ID)) {
			// Is from another Fuzz service
			$request_id = $request->header(RequestTracer::FUZZ_TRACE_ID);
		} else {
			$request_id = UUID::generate();
		}

		return $request_id;
	}
}
