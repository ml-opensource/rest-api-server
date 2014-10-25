<?php

namespace Fuzz\ApiServer\Exception;

class HttpException extends \RuntimeException
{
	/**
	 * HTTP status code.
	 * @var int
	 */
	private $status_code;

	/**
	 * A meaningful error code string.
	 * @var string
	 */
	private $error_code;

	/**
	 * Contextualizing data.
	 * @var mixed
	 */
	private $data;

	/**
	 * Additional response headers to send along.
	 * @var array
	 */
	private $headers = [];

	const ERROR_CODE = 'E_UNKNOWN';

	const STATUS_CODE = 500;

	/**
	 * @param string $data
	 * @param string $error_code
	 * @param array  $headers
	 */
	public function __construct($data = null, $error_code = null, $headers = [])
	{
		$this->data    = $data;
		$this->headers = $headers;

		return parent::__construct(is_null($error_code) ? static::ERROR_CODE : $error_code);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getHeaders()
	{
		return $this->headers;
	}
}
