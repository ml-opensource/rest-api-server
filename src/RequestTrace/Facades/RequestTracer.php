<?php

namespace Fuzz\ApiServer\RequestTrace\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class RequestTracer
 *
 * @method static string|null getRequestId()
 *
 * @package Fuzz\ApiServer\RequestTrace\Facades
 */
class RequestTracer extends Facade
{
	/**
	 * Header names
	 *
	 * @const string
	 */
	const ELB_TRACE_ID      = 'X-Amzn-Trace-Id';
	const FUZZ_TRACE_ID     = 'X-Fz-Trace-Id';
	const REQUEST_ID_HEADER = 'X-Request-Id';

	/**
	 * Get the facade accessor
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return self::class;
	}
}
