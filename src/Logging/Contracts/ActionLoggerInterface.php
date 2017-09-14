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
	 * @param string      $action
	 * @param string|null $resource
	 * @param string|null $resource_id
	 * @param string|null $note
	 * @param array       $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function log(string $action, string $resource = null, string $resource_id = null, string $note = null, array $meta = []): ActionLoggerInterface;

	/**
	 * Log an error action
	 *
	 * @param string      $action
	 * @param string|null $resource
	 * @param string|null $resource_id
	 * @param string|null $error
	 * @param string|null $note
	 * @param array       $meta
	 *
	 * @return \Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface
	 */
	public function error(string $action, string $resource = null, string $resource_id = null, string $error = null, string $note = null, array $meta = []): ActionLoggerInterface;

	/**
	 * Clear the message queue
	 */
	public function clearQueue();

	/**
	 * Get the message queue
	 *
	 * @return array
	 */
	public function getMessageQueue(): array;

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