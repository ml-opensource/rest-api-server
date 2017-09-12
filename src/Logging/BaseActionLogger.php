<?php

namespace Fuzz\ApiServer\Logging;

use Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface;
use Fuzz\ApiServer\Logging\Contracts\LoggableAction;
use Fuzz\ApiServer\Logging\Contracts\LoggableError;
use Illuminate\Http\Request;

/**
 * Class ActionLogger
 *
 * The ActionLogger maintains a queue of messages ready to be written to some message store
 *
 * @package Fuzz\ApiServer\Logging
 */
abstract class BaseActionLogger implements ActionLoggerInterface
{
	/**
	 * Agent performing actions
	 *
	 * @var string
	 */
	protected $agent_id;

	/**
	 * Message queue storage
	 *
	 * @var \Fuzz\ApiServer\Logging\Contracts\LoggableAction[]
	 */
	protected $message_queue = [];

	/**
	 * Request storage
	 *
	 * @var \Illuminate\Http\Request $request
	 */
	protected $request;

	/**
	 * Client IP for this request
	 *
	 * @var string
	 */
	protected $client_ip;

	/**
	 * Toggle for whether we should be logging actions or not
	 *
	 * @var boolean
	 */
	protected $logging_enabled = false;

	/**
	 * Client ID Storage
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * The model for action logs
	 *
	 * @var string
	 */
	protected $log_model;

	/**
	 * ActionLogLogger constructor.
	 *
	 * @param array                    $config
	 * @param \Illuminate\Http\Request $request
	 */
	public function __construct(array $config, Request $request)
	{
		$this->request         = $request;
		$this->client_ip       = $request->ip();
		$this->log_model       = $config['model_class'] ?? ActionLog::class;
		$this->logging_enabled = $config['enabled'];
	}

	/**
	 * Determine if logging is enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return $this->logging_enabled;
	}

	/**
	 * Log an action
	 *
	 * @param \Fuzz\ApiServer\Logging\Contracts\LoggableAction $event
	 * @param array                                            $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function log(LoggableAction $event, array $meta = []): ActionLoggerInterface
	{
		$event->setMeta($meta);

		if (! is_null($this->agent_id)) {
			$event->setAgentId($this->agent_id);
		}

		if (! is_null($this->client_id)) {
			$event->setClientId($this->client_id);
		}

		$this->message_queue[] = $event;

		return $this;
	}

	/**
	 * Log an error action
	 *
	 * @param \Fuzz\ApiServer\Logging\Contracts\LoggableError|null|string $error
	 * @param array                                                       $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function error(LoggableError $error, array $meta = []): ActionLoggerInterface
	{
		$error->setMeta($meta);

		if (! is_null($this->agent_id)) {
			$error->setAgentId($this->agent_id);
		}

		if (! is_null($this->client_id)) {
			$error->setClientId($this->client_id);
		}

		$this->message_queue[] = $error;

		return $this;
	}

	/**
	 * Clear the message queue
	 */
	public function clearQueue()
	{
		$this->message_queue = [];
	}

	/**
	 * Get the message queue
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\LoggableAction[]
	 */
	public function getMessageQueue(): array
	{
		return $this->message_queue;
	}

	/**
	 * Get all messages current in queue in array form
	 *
	 * @return array
	 */
	public function getMessages(): array
	{
		$messages = [];

		foreach ($this->message_queue as $message) {
			$messages[] = $message->toArray();
		}

		return $messages;
	}

	/**
	 * Get the current queue length
	 *
	 * @return int
	 */
	public function getQueueLength(): int
	{
		return count($this->message_queue);
	}

	/**
	 * Set the agent
	 *
	 * @param string $agent_id
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function setActionAgentId(string $agent_id): ActionLoggerInterface
	{
		$this->agent_id = $agent_id;

		$this->writeAgentToMessages();

		return $this;
	}

	/**
	 * Write the user agent to all messages in queue
	 *
	 * Why this is necessary: A limitation of this design is that using a factory method in our tests causes strange
	 * behavior because a user is not necessarily set at the point in time that the model event gets fired. We reserve
	 * the setting of the user agent until this function is called (usually in flushQueue).
	 */
	public function writeAgentToMessages()
	{
		if (is_null($this->agent_id)) {
			// Don't waste time setting null to null
			return;
		}

		foreach ($this->message_queue as $i => $message) {
			$message->setAgentId($this->agent_id);
		}
	}

	/**
	 * Set the client id for all actions in this request
	 *
	 * @param string $client_id
	 *
	 * @return \Fuzz\ApiServer\Logging\ActionLogger|\Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function setClientId(string $client_id): ActionLoggerInterface
	{
		$this->client_id = $client_id;

		foreach ($this->message_queue as $i => $message) {
			$message->setClientId($client_id);
		}

		return $this;
	}

	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	abstract public function flushQueue(): bool;
}
