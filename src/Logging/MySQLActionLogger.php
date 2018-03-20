<?php

namespace Fuzz\ApiServer\Logging;

/**
 * Class MySQLActionLogger
 *
 * The MySQLActionLogger maintains a queue of messages ready to be written to a MySQL DB.
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

		$log_model = $this->getModelClass();

		$success = $log_model::insert($this->getMessageQueue());

		if ($success) {
			$this->clearQueue();
		}

		return $success;
	}

	/**
	 * Resolve the model class
	 *
	 * @return string
	 */
	public function getModelClass(): string
	{
		return $this->config['mysql']['model_class'] ?? ActionLog::class;
	}
}
