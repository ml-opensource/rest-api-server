<?php

namespace Fuzz\ApiServer\Logging\Middleware;

use Closure;
use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ActionLoggerMiddleware
 *
 * ActionLoggerMiddleware@terminate is fired at the end of the request, after it has been sent to the client. It
 * collects data about the request and writes it to the log.
 *
 * @package Fuzz\ApiServer\Logging\Middleware
 */
abstract class ActionLoggerMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 *
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		return $next($request);
	}

	/**
	 * Handle some logic after the response has been sent to the client
	 *
	 * @param \Illuminate\Http\Request                   $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 *
	 * @return bool
	 */
	public function terminate(Request $request, Response $response): bool
	{
		// Don't attempt to add messages to queue if logging is disabled
		if (! ActionLogger::isEnabled()) {
			return false;
		}

		if (! $this->shouldLogActions()) {
			return false;
		}

		if ($this->hasActionAgent()) {
			ActionLogger::setActionAgentId($this->getActionAgentId());
		}

		ActionLogger::setClientId($this->getActionClientId());

		ActionLogger::flushQueue();

		return true;
	}

	/**
	 * Determine if we should log any actions
	 *
	 * @return bool
	 */
	abstract public function shouldLogActions(): bool;

	/**
	 * Determine if this request has an action agent
	 *
	 * @return bool
	 */
	abstract public function hasActionAgent(): bool;

	/**
	 * Retrieve the current action agent's ID
	 *
	 * @return string
	 */
	abstract public function getActionAgentId(): string;

	/**
	 * Retrieve the current client's ID
	 *
	 * @return string
	 */
	abstract public function getActionClientId(): string;
}
