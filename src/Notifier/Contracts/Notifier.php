<?php

namespace Fuzz\ApiServer\Notifier\Contracts;

/**
 * Interface Notifier
 *
 * A Notifier is responsible for receiving a message and notifying its target.
 *
 * @package Fuzz\ApiServer\Notifier\Contracts
 */
interface Notifier
{
	/**
	 * Severity levels
	 *
	 * @const string
	 */
	const MINOR  = 'Minor';
	const URGENT = 'Urgent';
	const SEVERE = 'Severe';

	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $environment
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool;

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param string $environment
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool;
}