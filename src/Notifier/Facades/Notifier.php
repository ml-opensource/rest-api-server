<?php

namespace Fuzz\ApiServer\Notifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Notifier
 *
 * @method static notifyError(\Throwable $e, string $severity = null, array $meta = [])
 * @method static notifyInfo(string $event, array $meta = [])
 *
 * @package Fuzz\ApiServer\Logging\Facades
 */
class Notifier extends Facade
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
