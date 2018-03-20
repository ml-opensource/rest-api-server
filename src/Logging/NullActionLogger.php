<?php

namespace Fuzz\ApiServer\Logging;

/**
 * Class NullActionLogger
 *
 * NullActionLogger does not log actions
 *
 * @package Fuzz\ApiServer\Logging
 */
class NullActionLogger extends BaseActionLogger
{
	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	public function flushQueue(): bool
	{
		// Pass

		return true;
	}
}
