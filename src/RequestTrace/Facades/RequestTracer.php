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
	 * Get the facade accessor
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return self::class;
	}
}
