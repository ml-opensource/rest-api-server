<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse;

/**
 * Class FailedResponse
 *
 * A FailedResponse wraps an a failed Guzzle response.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Http
 */
class FailedResponse implements CompositeResponse
{
	/**
	 * Json response content type
	 *
	 * @const string
	 */
	const JSON_RESPONSE_TYPE = 'application/json';

	/**
	 * Response status code
	 *
	 * @const int
	 */
	const STATUS_CODE = 503;

	/**
	 * Request URI
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Error reason
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * FailedResponse constructor.
	 *
	 * @param string $reason
	 */
	public function __construct(string $reason)
	{
		$this->reason = $reason;
	}

	/**
	 * Access the response content
	 *
	 * @return string
	 */
	public function getContent(): string
	{
		return json_encode($this->getErrorResponse());
	}

	/**
	 * Get the error response
	 *
	 * @return array
	 */
	public function getErrorResponse(): array
	{
		return [
			'error'         => 'service_unavailable',
			'error_message' => $this->reason,
		];
	}

	/**
	 * Set the response content
	 *
	 * @param string $content
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setContent(string $content): CompositeResponse
	{
		throw new \LogicException('Content cannot be set for error responses.');
	}

	/**
	 * Access the response status code
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return self::STATUS_CODE;
	}

	/**
	 * Ser the response status code
	 *
	 * @param int $status_code
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setStatusCode(int $status_code): CompositeResponse
	{
		throw new \LogicException('Status Code cannot be set for error responses.');
	}

	/**
	 * Access the response headers
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		return [];
	}

	/**
	 * Set the response headers
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setHeaders(array $headers): CompositeResponse
	{
		throw new \LogicException('Headers cannot be set for error responses.');
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 *
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), true);
	}

	/**
	 * Serialize the response to array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getErrorResponse();
	}

	/**
	 * Get the response json serialized to an array
	 *
	 * @return array
	 */
	public function getSerializedContent(): array
	{
		return $this->getErrorResponse();
	}

	/**
	 * Access the URI
	 *
	 * @return string
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * Set the URI
	 *
	 * @param string $uri
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setUri(string $uri): CompositeResponse
	{
		$this->uri = $uri;

		return $this;
	}
}
