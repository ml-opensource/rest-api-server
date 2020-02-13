<?php

namespace Fuzz\ApiServer\Notifier\Handlers\Email;

use Carbon\Carbon;
use Fuzz\ApiServer\Notifier\BaseNotifier;
use Fuzz\ApiServer\Notifier\Contracts\Notifier;
use Fuzz\ApiServer\Notifier\Traits\ReadsRequestId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailHandler extends BaseNotifier implements Notifier
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
		// Don't send these emails for ignored environments
		if (in_array($environment, $this->config['ignore_environments'])) {
			return false;
		}

		$trace      = $e->getTraceAsString();
		$now_utc    = Carbon::now();
		$now_est    = Carbon::now($this->config['local_tz']);
		$meta       = print_r($meta, true);
		$error      = get_class($e);
		$message    = $e->getMessage();
		$hostname   = gethostname();
		$os         = php_uname();
		$request_id = $this->getRequestId();
		$body       = <<<BODY
<b>Environment</b>: $environment
<b>Hostname:</b>: $hostname
<b>OS:</b>: $os
<b>Severity</b>: $severity
<b>Request-Id</b>: $request_id

<b>Occurred At (UTC)</b>: $now_utc
<b>Occurred At (EST)</b>: $now_est

<b>Meta</b>: <pre>$meta</pre>

<b>Error</b>: $error
<b>Message</b>: $message
<b>Stack Trace</b>:
$trace
BODY;

		$app_name = config('app.name');
		$subject  = "$app_name Encountered an Error on $environment: $error";

		return self::dispatchEmail($error, $message, $subject, $body, $environment, $now_utc);
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
		return false;
	}

	/**
	 * Dispatch the notification email
	 *
	 * @param string         $error
	 * @param string         $message
	 * @param string         $subject
	 * @param string         $body
	 * @param string         $env
	 * @param \Carbon\Carbon $now_utc
	 *
	 * @return bool
	 */
	protected function dispatchEmail(string $error, string $message, string $subject, string $body, string $env, Carbon $now_utc): bool
	{
		if (! $this->shouldDispatchEmail($error, $message)) {
			return false;
		}

		Mail::to($this->config['receivers'])->queue(new NotificationEmail($subject, $body, $env, $now_utc));
		
		Cache::put($this->makeCacheKey($error, $message), true, $this->config['notification_cooldown_min'] * 60);

		return true;
	}

	/**
	 * Determine if an email should be dispatched for this notification
	 *
	 * @param string $error
	 * @param string $message
	 *
	 * @return bool
	 */
	protected function shouldDispatchEmail(string $error, string $message): bool
	{
		return is_null(Cache::get($this->makeCacheKey($error, $message)));
	}

	/**
	 * Generate EmailNotifier's cache key
	 *
	 * @param string $error
	 * @param string $message
	 *
	 * @return string
	 */
	protected function makeCacheKey(string $error, string $message): string
	{
		$hashed = md5("$error:$message");

		return "{$this->config['cache_key_prefix']}:$hashed";
	}
}
