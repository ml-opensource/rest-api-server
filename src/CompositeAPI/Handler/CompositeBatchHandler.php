<?php

namespace Fuzz\ApiServer\CompositeAPI\Handler;

use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleResponse;
use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler;
use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Class CompositeBatchHandler
 *
 * The CompositeBatchHandler runs parallel HTTP requests asynchronously.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Handler
 */
class CompositeBatchHandler implements CompositeHandler
{
	/**
	 * Default Guzzle options
	 *
	 * @const array
	 */
	const DEFAULT_OPTIONS = ['http_errors' => false, 'timeout' => 10];

	/**
	 * @var \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest[]
	 */
	protected $requests = [];

	/**
	 * @var \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse[]
	 */
	protected $responses = [];

	/**
	 * @var string
	 */
	protected $base_url;

	/**
	 * @var \GuzzleHttp\Client
	 */
	protected $client;

	/**
	 * @var \GuzzleHttp\Promise\PromiseInterface[]
	 */
	protected $await = [];

	/**
	 * CompositeHandler constructor.
	 *
	 * @param string $base_url
	 */
	public function __construct(string $base_url)
	{
		$this->base_url = $base_url;
	}

	/**
	 * Access the HTTP client
	 *
	 * @return \GuzzleHttp\Client
	 */
	public function getClient(): Client
	{
		if (is_null($this->client)) {
			return $this->client = new Client(['base_uri' => $this->base_url]);
		}

		return $this->client;
	}

	/**
	 * Set the HTTP client
	 *
	 * @param \GuzzleHttp\Client $client
	 */
	public function setClient(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Add a new request to the handler
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $request
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function addRequest(CompositeRequest $request): CompositeHandler
	{
		$this->requests[] = $request;

		return $this;
	}

	/**
	 * Run through all requests
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function run(): CompositeHandler
	{
		// Send all the requests async
		foreach ($this->requests as $request) {
			$guzzle_request = $this->toGuzzleRequest($request);
			$request_uri = $request->getURI();

			$this->await[$request_uri] = $this->getClient()
				->sendAsync($guzzle_request, self::DEFAULT_OPTIONS)
				->then(function ($response) use ($request, $request_uri) {
					unset($this->await[$request_uri]);

					$response = $this->readResponse($response)->setUri($request_uri);

					$this->responses[$request_uri] = $response;
				})->otherwise(function ($err) {
					// @todo need to handle this case
				});
		}

		// After sending all async requests, await all of their responses
		foreach ($this->await as $promise) {
			$promise->wait();
		}

		return $this;
	}

	/**
	 * Retrieve all the available responses
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse[]
	 */
	public function getResponses(): array
	{
		return $this->responses;
	}

	/**
	 * Read each response
	 *
	 * @return array
	 */
	public function readResponses(): array
	{
		return array_map(function ($item) {
			return $item->toArray();
		}, $this->responses);
	}

	/**
	 * Parse a CompositeRequest into a Guzzle Request
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $request
	 *
	 * @return \GuzzleHttp\Psr7\Request
	 */
	protected function toGuzzleRequest(CompositeRequest $request): Request
	{
		$content = $request->hasContent() ? $request->getContent() : null;

		return new Request($request->getMethod(), $request->getURI(), $request->getHeaders(), $content);
	}

	/**
	 * Read a Guzzle response into a CompositeResponse
	 *
	 * @param \GuzzleHttp\Psr7\Response $response
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse
	 */
	protected function readResponse(Response $response)
	{
		$composite_response = new CompositeGuzzleResponse;

		// @todo add factory
		return $composite_response->setStatusCode($response->getStatusCode())
			->setContent($response->getBody())
			->setHeaders($response->getHeaders());
	}
}
