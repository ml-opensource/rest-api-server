<?php

namespace Fuzz\ApiServer\Logging\Contracts;

/**
 * Interface ActionLoggerInterface
 *
 * An ActionLogger maintains a queue of messages ready to be written to some message store
 *
 * @package Fuzz\ApiServer\Logging
 */
interface ActionLoggerInterface
{
	/**
	 * Possible actions
	 *
	 * @const string
	 */
	const STORE_ACTION   = 'store';
	const ACCESS_ACTION  = 'access';
	const UPDATE_ACTION  = 'update';
	const DESTROY_ACTION = 'destroy';

	/**
	 * Determine if logging is enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool;

	/**
	 * Log an action
	 *
	 * @param \Fuzz\ApiServer\Logging\Contracts\LoggableAction $event
	 * @param array                                            $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function log(LoggableAction $event, array $meta = []): ActionLoggerInterface;

	/**
	 * Log an error action
	 *
	 * @param \Fuzz\ApiServer\Logging\Contracts\LoggableError|null|string $error
	 * @param array                                                       $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function error(LoggableError $error, array $meta = []): ActionLoggerInterface;

	/**
	 * Clear the message queue
	 */
	public function clearQueue();

	/**
	 * Get the message queue
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\LoggableAction[]
	 */
	public function getMessageQueue(): array;

	/**
	 * Get all messages current in queue in array form
	 *
	 * @return array
	 */
	public function getMessages(): array;

	/**
	 * Get the current queue length
	 *
	 * @return int
	 */
	public function getQueueLength(): int;

	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	public function flushQueue(): bool;

	/**
	 * Set the client id for all actions
	 *
	 * @param string $client_id
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function setClientId(string $client_id): ActionLoggerInterface;

	/**
	 * Set the agent id for all actions
	 *
	 * @param string $agent_id
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function setActionAgentId(string $agent_id): ActionLoggerInterface;
}