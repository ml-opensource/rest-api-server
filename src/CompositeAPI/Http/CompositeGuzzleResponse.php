<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse;

/**
 * Class CompositeGuzzleResponse
 *
 * A CompositeGuzzleResponse wraps an HTTP response.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Http
 */
class CompositeGuzzleResponse implements CompositeResponse
{
	/**
	 * Json response content type
	 *
	 * @const string
	 */
	const JSON_RESPONSE_TYPE = 'application/json';

	/**
	 * Response content storage
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Request uri storage
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Response status storage
	 *
	 * @var int
	 */
	private $status_code;

	/**
	 * Response header storage
	 *
	 * @var array
	 */
	private $headers = [];

	/**
	 * Access the response content
	 *
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
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
		$this->content = $content;

		return $this;
	}

	/**
	 * Access the response status code
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->status_code;
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
		$this->status_code = $status_code;

		return $this;
	}

	/**
	 * Access the response headers
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
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
		$this->headers = $headers;

		return $this;
	}

	/**
	 * Serialize the response to array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'status'  => $this->getStatusCode(),
			'uri'     => $this->getUri(),
			'headers' => $this->getHeaders(),
			'body'    => $this->getSerializedContent(),
		];
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
	 * Get the response json serialized to an array
	 *
	 * @return array
	 */
	public function getSerializedContent(): array
	{
		if (! $this->isJsonResponse()) {
			throw new \RuntimeException('Attempted to serialize a non-json response.');
		}

		return json_decode($this->getContent(), true);
	}

	/**
	 * Determine if the response content is JSON
	 *
	 * @return bool
	 */
	protected function isJsonResponse(): bool
	{
		$headers = $this->getHeaders();

		return isset($headers['Content-Type']) && $headers['Content-Type'] === [self::JSON_RESPONSE_TYPE];
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
