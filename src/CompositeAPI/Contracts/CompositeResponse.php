<?php

namespace Fuzz\ApiServer\CompositeAPI\Contracts;

/**
 * Interface CompositeResponse
 *
 * CompositeResponse is an abstraction around HTTP responses. Its purpose is to collect the data and
 * provide an easy API to serialize it.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Contracts
 */
interface CompositeResponse
{
	/**
	 * Get the response content
	 *
	 * @return string
	 */
	public function getContent(): string;

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
}