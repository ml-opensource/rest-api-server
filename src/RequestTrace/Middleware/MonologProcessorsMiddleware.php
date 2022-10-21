<?php

namespace Fuzz\ApiServer\RequestTrace\Middleware;

use Closure;
use Fuzz\ApiServer\RequestTrace\Facades\RequestTracer;
use Fuzz\ApiServer\RequestTrace\Utility\RequestIdLogProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class MonologProcessorsMiddleware
 *
 * MonologProcessorsMiddleware attaches processors to the Monolog instance
 *
 * @package Fuzz\ApiServer\RequestTrace\Middleware;
 */
class MonologProcessorsMiddleware
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
        // Workaround for change in Illuminate\Log\Writer between Laravel 5.5 and 5.6
        if (version_compare(app()->version(), '5.6', '<')) {
            $monolog = Log::getMonolog();
        } else {
            $monolog = Log::getLogger();
        }

		$monolog->pushProcessor(new RequestIdLogProcessor(RequestTracer::getRequestId()));

		return $next($request);
	}
}
