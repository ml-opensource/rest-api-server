<?php

namespace Fuzz\ApiServer\Logging\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface LogModel
 *
 * @package Fuzz\ApiServer\Logging
 */
interface LoggableAction extends Arrayable
{
	public function fromArray(array $data): LoggableAction;

	public function setAgentId(string $agent_id): LoggableAction;

	public function setClientId(string $client_id): LoggableAction;

	public function setMeta(array $meta): LoggableAction;
}