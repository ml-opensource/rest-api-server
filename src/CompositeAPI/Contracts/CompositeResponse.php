<?php

namespace Fuzz\ApiServer\CompositeAPI\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Interface CompositeResponse
 *
 * CompositeResponse is an abstraction around HTTP responses. Its purpose is to collect the data and
 * provide an easy API to serialize it.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Contracts
 */
interface CompositeResponse extends Jsonable, Arrayable
{
	/**
	 * Get the response content
	 *
	 * @return string
	 */
	public function getContent(): string;

	/**
	 * Get the response json serialized to an array
	 *
	 * @return array
	 */
	public function getSerializedContent(): array;

	/**
	 * Set the response content
	 *
	 * @param string $content
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setContent(string $content): CompositeResponse;

	/**
	 * Get the response status code
	 *
	 * @return int
	 */
	public function getStatusCode(): int;

	/**
	 * Set the response status code
	 *
	 * @param int $status_code
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setStatusCode(int $status_code): CompositeResponse;

	/**
	 * Get the response headers
	 *
	 * @return array
	 */
	public function getHeaders(): array;

	/**
	 * Set the response headers
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setHeaders(array $headers): CompositeResponse;

	/**
	 * Access the URI
	 *
	 * @return string
	 */
	public function getUri(): string;

	/**
	 * Set the URI
	 *
	 * @param string $uri
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	public function setUri(string $uri): CompositeResponse;
}