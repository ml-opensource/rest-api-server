<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;

/**
 * Class CompositeGuzzleRequest
 *
 * CompositeGuzzleRequest wraps an HTTP request.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Http
 */
class CompositeGuzzleRequest implements CompositeRequest
{
	/**
	 * Request content storage
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Request URI storage
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Request headers storage
	 *
	 * @var array
	 */
	private $headers;

	/**
	 * Request method storage
	 *
	 * @var string
	 */
	private $method = self::HTTP_GET;

	/**
	 * Set the request content
	 *
	 * @param string $content
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setContent(string $content): CompositeRequest
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Set thre request headers
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setHeaders(array $headers): CompositeRequest
	{
		$this->headers = $headers;

		return $this;
	}

	/**
	 * Determine if the request has content
	 *
	 * @return bool
	 */
	public function hasContent(): bool
	{
		return ! is_null($this->content);
	}

	/**
	 * Access the request content
	 *
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * Access the request headers
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Set the requet method
	 *
	 * @param string $method
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setMethod(string $method): CompositeRequest
	{
		$this->method = $method;

		return $this;
	}

	/**
	 * Get the request method
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Set the request URI
	 *
	 * @param string $uri
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setURI(string $uri): CompositeRequest
	{
		$this->uri = $uri;

		return $this;
	}

	/**
	 * Get the request URI
	 *
	 * @return string
	 */
	public function getURI(): string
	{
		return $this->uri;
	}
}
