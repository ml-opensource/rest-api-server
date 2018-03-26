<?php

namespace Fuzz\ApiServer\Notifier\Handlers\ActionLog;

use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Notifier\BaseNotifier;
use Fuzz\ApiServer\Notifier\Contracts\Notifier;
use Fuzz\ApiServer\Notifier\Traits\ReadsRequestId;
use Illuminate\Http\Request as ConcreteRequest;
use Illuminate\Support\Facades\Request;

class ActionLogHandler extends BaseNotifier implements Notifier
{
	use ReadsRequestId;

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
	public function notifyError(\Throwable $e, string $environment, string $severity = self::URGENT, array $meta = []): bool
	{
		if (! $this->shouldLog()) {
			return false;
		}

		/** @var \Illuminate\Http\Request $request */
		$request = $this->getRequest();
		$error   = get_class($e);
		$sapi    = php_sapi_name();

		if ($sapi === 'cli') {
			$resource = 'cli';

			$meta = [
				'php_sapi'        => $sapi,
			];
		} else {
			$resource = sprintf('%s %s', $request->getMethod(), $request->getUri());

			$input = config('app.debug', false) ? $request->getContent() : 'redacted';

			$meta = [
				'php_sapi'        => $sapi,
				'input'           => $input,
				'method'          => $request->getMethod(),
				'request_headers' => $request->headers->all(),
			];
		}

		ActionLogger::error('notified_error_event', $resource, null, substr($error, 0, 255), null, $meta);

		return true;
	}

	/**
	 * Notify with some info
	 *
	 * @param string $event
	 * @param string $environment
	 * @param array  $meta
	 *
	 * @return bool
	 */
	public function notifyInfo(string $event, string $environment, array $meta = []): bool
	{
		if (! $this->shouldLog()) {
			return false;
		}

		ActionLogger::log($event, null, null, null, null, $meta);

		return true;
	}

	/**
	 * Only log if the action logger is set
	 *
	 * @return bool
	 */
	protected function shouldLog(): bool
	{
		return app()->bound(ActionLogger::class);
	}

	/**
	 * Get the concrete request
	 *
	 * @return \Illuminate\Http\Request
	 */
	protected function getRequest(): ConcreteRequest
	{
		return  Request::instance();
	}
}
