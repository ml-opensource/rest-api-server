<?php

namespace Fuzz\ApiServer\RequestTrace;

/**
 * Class ActionLog
 *
 * @package Fuzz\ApiServer\RequestTrace
 */
class RequestId
{
	/**
	 * Request ID storage
	 *
	 * @var null|string
	 */
	protected $request_id;

	/**
	 * RequestIdLogProcessor constructor.
	 *
	 * @param null|string $request_id
	 */
	public function __construct(string $request_id = null)
	{
		$this->request_id = $request_id;
	}

	/**
	 * Access the request Id
	 *
	 * @return null|string
	 */
	public function getRequestId()
	{
		return $this->request_id;
	}
}
