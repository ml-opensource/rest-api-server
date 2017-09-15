<?php

namespace Fuzz\ApiServer\CompositeAPI\Contracts;

/**
 * Interface CompositeRequest
 *
 * A CompositeRequest wraps an HTTP request and holds its data.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Contracts
 */
interface CompositeRequest
{
	/**
	 * Available methods
	 *
	 * @const string
	 */
	const HTTP_GET    = 'get';
	const HTTP_PUT    = 'put';
	const HTTP_POST   = 'post';
	const HTTP_PATCH  = 'patch';
	const HTTP_DELETE = 'delete';

	/**
	 * Set the request URI
	 *
	 * @param string $uri
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setURI(string $uri): CompositeRequest;

	/**
	 * Get the request URI
	 *
	 * @return string
	 */
	public function getURI(): string;

	/**
	 * Set the request content
	 *
	 * @param string $content
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setContent(string $content): CompositeRequest;

	/**
	 * Determine if the CompositeRequest as content
	 *
	 * @return bool
	 */
	public function hasContent(): bool;

	/**
	 * Get the request content
	 *
	 * @return mixed
	 */
	public function getContent(): string; // @todo nullable string return

	/**
	 * Set the request method
	 *
	 * @param string $method
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setMethod(string $method): CompositeRequest;

	/**
	 * Get the request method
	 *
	 * @return string
	 */
	public function getMethod(): string;

	/**
	 * Set the request headers
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function setHeaders(array $headers): CompositeRequest;

	/**
	 * Get the request headers
	 *
	 * @return array
	 */
	public function getHeaders(): array;
}