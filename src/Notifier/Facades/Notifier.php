<?php

namespace Fuzz\ApiServer\Notifier\Facades;

use Fuzz\ApiServer\Notifier\Contracts\Notifier as INotifier;
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
	 * Severity levels
	 *
	 * @const string
	 */
	const MINOR  = INotifier::MINOR;
	const URGENT = INotifier::URGENT;
	const SEVERE = INotifier::SEVERE;

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
