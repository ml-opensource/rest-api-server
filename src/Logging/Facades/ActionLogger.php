<?php

namespace Fuzz\ApiServer\Logging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ActionLog
 *
 * @package Fuzz\ApiServer\Logging\Facades
 */
class ActionLogger extends Facade
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
