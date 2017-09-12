<?php

namespace Fuzz\ApiServer\Logging;

/**
 * Class MySQLActionLogger
 *
 * The ActionLMySQLActionLoggerogger maintains a queue of messages ready to be written to a MySQL DB.
 *
 * @package Fuzz\ApiServer\Logging
 */
class MySQLActionLogger extends BaseActionLogger
{
	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	public function flushQueue(): bool
	{
		if ($this->getQueueLength() === 0) {
			return false;
		}

		$log_model = $this->log_model;

		$success = $log_model::insert($this->getMessages());

		if ($success) {
			$this->clearQueue();
		}

		return $success;
	}
}
