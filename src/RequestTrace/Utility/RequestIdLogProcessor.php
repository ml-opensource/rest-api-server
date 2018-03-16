<?php

namespace Fuzz\ApiServer\RequestTrace\Utility;

/**
 * Class RequestIdLogProcessor
 *
 * Handles Monolog proceessing for attaching RequestId to a log line.
 *
 * @package Fuzz\ApiServer\RequestTrace\Utility
 */
class RequestIdLogProcessor
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
	public function __construct(?string $request_id)
	{
		$this->request_id = $request_id;
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 */
	public function __invoke(array $record): array
	{
		$record['message'] = "[X-Request-Id: $this->request_id] {$record['message']}";

		return $record;
	}
}