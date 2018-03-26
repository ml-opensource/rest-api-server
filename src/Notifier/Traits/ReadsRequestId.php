<?php

namespace Fuzz\ApiServer\Notifier\Traits;

use Fuzz\ApiServer\RequestTrace\Facades\RequestTracer;

trait ReadsRequestId
{
	/**
	 * Get the request ID, if it is set
	 *
	 * @return null|string
	 */
	public function getRequestId(): ?string
	{
		if (! app()->bound(RequestTracer::class)) {
			return null;
		}

		return RequestTracer::getRequestId();
	}
}