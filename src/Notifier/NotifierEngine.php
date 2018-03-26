<?php

namespace Fuzz\ApiServer\Notifier;

use Aws\ElasticsearchService\ElasticsearchPhpHandler;
use Elasticsearch\ClientBuilder;
use Fuzz\ApiServer\Notifier\Contracts\Notifier;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Manager;

class NotifierEngine extends BaseNotifier
{
	/**
	 * Notify with an error
	 *
	 * @param \Throwable $e
	 * @param string     $severity
	 * @param array      $meta
	 *
	 * @return bool
	 */
	public function notifyError(\Throwable $e, string $severity = Notifier::URGENT, array $meta = []): bool
	{
		$all_succeeded = true;
		$env = app()->environment();

		foreach ($this->config['handler_stack'] as $class => $config) {
			/** @var \Fuzz\ApiServer\Notifier\Contracts\Notifier $handler */
			$handler = new $class($config);

			if (! $handler instanceof Notifier) {
				throw new \LogicException("Expected $class to implement " . Notifier::class . '.');
			}

			if (! $handler->notifyError($e, $env, $severity, $meta)) {
				$all_succeeded = false;
			}
		}

		return $all_succeeded;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, array $meta = []): bool
	{
		$all_succeeded = true;
		$env = app()->environment();

		foreach ($this->config['handler_stack'] as $class => $config) {
			/** @var \Fuzz\ApiServer\Notifier\Contracts\Notifier $handler */
			$handler = new $class($config);

			if (! $handler instanceof Notifier) {
				throw new \LogicException("Expected $class to implement " . Notifier::class . '.');
			}

			if (! $handler->notifyInfo($event, $env, $meta)) {
				$all_succeeded = false;
			}
		}

		return $all_succeeded;
	}
}
