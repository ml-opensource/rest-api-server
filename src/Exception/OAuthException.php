<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;
use League\OAuth2\Server\Exception\OAuthException as BaseException;

class OAuthException extends BaseException
{
	/**
	 * The default message to use, if no message was specified.
	 *
	 * @var string
	 */
	const DEFAULT_MESSAGE = 'An unknown error occurred.';

	/**
	 * The default error type to use, if no error type was specified.
	 *
	 * @var string
	 */
	const DEFAULT_ERROR_TYPE = 'unknown';

	/**
	 * The default status code to use, if no status code was specified.
	 *
	 * @var int
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_INTERNAL_SERVER_ERROR;

	/**
	 * Error data.
	 *
	 * @var mixed
	 */
	public $errorData;

	/**
	 * {@inheritdoc}
	 */
	public function __construct($message = null, $error_data = null, $error_type = null, $http_status_code = null)
	{
		$this->httpStatusCode = is_null($http_status_code) ? static::DEFAULT_STATUS_CODE : $http_status_code;
		$this->errorType      = is_null($error_type) ? static::DEFAULT_ERROR_TYPE : $error_type;
		$this->errorData      = $error_data;

		parent::__construct(is_null($message) ? static::DEFAULT_MESSAGE : $message);
	}
}
