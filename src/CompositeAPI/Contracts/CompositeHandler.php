<?php

namespace Fuzz\ApiServer\CompositeAPI\Contracts;

/**
 * Interface CompositeHandler
 *
 * A CompositeHandler wraps a set of requests and is responsible for sending them out and collecting
 * their responses.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Contracts
 */
interface CompositeHandler
{
	/**
	 * CompositeHandler constructor.
	 *
	 * @param string $base_url
	 */
	public function __construct(string $base_url);

	/**
	 * Add a new request to the handler
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $request
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function addRequest(CompositeRequest $request): CompositeHandler;

	/**
	 * Run through all requests
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function run(): CompositeHandler;

	/**
	 * Retrieve all the available responses
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse[]
	 */
	public function getResponses(): array;
}