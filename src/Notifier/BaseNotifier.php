<?php

namespace Fuzz\ApiServer\Notifier;

abstract class BaseNotifier
{
	/**
	 * Notifier configuration
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * BaseNotifier constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	}
}
