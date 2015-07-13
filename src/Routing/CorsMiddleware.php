<?php

namespace Fuzz\ApiServer\Routing;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 * @return mixed
	 */
	public function handle(Request $request, \Closure $next)
	{
		$response = $next($request);

		if ($this->isPreflightRequest($request)) {
			$this->addPreflightHeaders($request, $response);
		} elseif ($this->isCorsRequest($request) && $request->header('Origin') !== $request->getSchemeAndHttpHost()) {
			$this->addCorsHeaders($request, $response);
		}

		return $response;
	}

	/**
	 * Check if a request is a cross-origin request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return bool
	 */
	private function isCorsRequest(Request $request)
	{
		return $request->headers->has('Origin');
	}

	/**
	 * Check if a request is a preflight request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return bool
	 */
	private function isPreflightRequest(Request $request)
	{
		return $request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method') && $this->isCorsRequest($request);
	}

	/**
	 * Add pre-flight headers to the response.
	 *
	 * @param \Illuminate\Http\Request                   $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 * @return void
	 */
	private function addPreflightHeaders(Request $request, Response $response)
	{
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set('Access-Control-Allow-Headers', strtoupper($request->headers->get('Access-Control-Request-Headers')));
		$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE');
	}

	/**
	 * Add CORS headers to the response.
	 *
	 * @param \Illuminate\Http\Request                   $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 * @return void
	 */
	private function addCorsHeaders(Request $request, Response $response)
	{
		$vary_headers   = array_filter(explode(', ', $request->header('Vary')));
		$vary_headers[] = 'Origin';
		$response->headers->set('Vary', implode(', ', array_unique($vary_headers)));
	}
}
