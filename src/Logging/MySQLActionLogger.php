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

	/**
	 * Get the message queue
	 *
	 * @return array
	 */
	public function getMessageQueue(): array
	{
		$this->loadRequestId();

		// Apply the request ID retroactively, if it is set
		return array_map(function (array $event) {
			$event['meta']       = json_encode($event['meta']);
			$event['request_id'] = $this->request_id;

			return $event;
		}, $this->message_queue);
	}
}
