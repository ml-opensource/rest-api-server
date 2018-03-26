<?php

namespace Fuzz\ApiServer\Logging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ActionLogger
 *
 * @method static \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface log(string $action, string $resource = null, string $resource_id = null, string $note = null, array $meta = [])
 * @method static \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface error(string $action, string $resource = null, string $resource_id = null, string $error = null, string $note = null, array $meta = [])
 * @method static \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface setClientId(string $client_id)
 * @method static \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface setActionAgentId(string $agent_id)
 * @method static clearQueue()
 * @method static array getMessageQueue()
 * @method static int getQueueLength()
 * @method static bool flushQueue()
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
