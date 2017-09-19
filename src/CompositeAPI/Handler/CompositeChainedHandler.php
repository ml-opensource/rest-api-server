<?php

namespace Fuzz\ApiServer\CompositeAPI\Handler;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler;
use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;

/**
 * Class CompositeChainedHandler
 *
 * The CompositeChainedHandler runs HTTP requests in order.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Handler
 */
class CompositeChainedHandler extends CompositeBatchHandler implements CompositeHandler
{
	/**
	 * Regex for tokens in chain requests
	 *
	 * @const string
	 */
	const CHAIN_TOKEN_REGEX = '/\%{([^}]+)}/';
	const TOKEN_START       = '%{';
	const TOKEN_END         = '}';
	const VALUE_NOT_EXISTS  = 'CompositeChainedHandler.VALUE_NOT_EXISTS';

	/**
	 * The head of the linked list of requests
	 *
	 * @var \Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest
	 */
	private $head;

	/**
	 * The tail of the linked list of requests
	 *
	 * @var \Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest
	 */
	private $last;

	/**
	 * All returned data, keyed by reference ID
	 *
	 * @var array
	 */
	private $reference_data = [];

	/**
	 * Add a new request to the handler
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $request
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function addRequest(CompositeRequest $request): CompositeHandler
	{
		// First request in list
		if (is_null($this->head)) {
			$this->head = $request;
			$this->last = $request;

			return $this;
		}

		$this->last->setNext($request);
		$this->last = $request;

		return $this;
	}

	/**
	 * Run through all requests
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler
	 */
	public function run(): CompositeHandler
	{
		$current = $this->head;

		while (! is_null($current)) {
			$original_uri = $current->getURI();

			$this->parseTokensIntoRequest($current);

			$guzzle_request = $this->toGuzzleRequest($current);

			$response = $this->readResponse($this->sendRequest($guzzle_request))
				->setUri($original_uri);

			$this->responses[$current->referenceId()] = $response;
			$this->reference_data[$current->referenceId()] = json_decode($response->getContent(), true);

			$current = $current->next();
		}

		return $this;
	}

	/**
	 * Send an HTTP request with Guzzle
	 *
	 * @param \GuzzleHttp\Psr7\Request $request
	 *
	 * @return \GuzzleHttp\Psr7\Response
	 */
	protected function sendRequest(Request $request): Response
	{
		return $this->getClient()->send($request, self::DEFAULT_OPTIONS);
	}

	/**
	 * Parse data into tokens encoded in the request
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $request
	 */
	protected function parseTokensIntoRequest(CompositeRequest $request)
	{
		$request->setURI($this->parseDataIntoTokens($request->getURI(), $this->reference_data));

		if ($request->hasContent()) {
			$parsed = $this->parseDataIntoTokens($request->getContent(), $this->reference_data);

			$request->setContent($parsed);
		}
	}

	/**
	 * Replace tokens with the data at their path
	 *
	 * @param string $subject
	 * @param array  $data
	 *
	 * @return string
	 */
	protected function parseDataIntoTokens(string $subject, array $data): string
	{
		$matches = [];
		$found = preg_match_all(self::CHAIN_TOKEN_REGEX, $subject, $matches);

		if ($found === 0) {
			return $subject;
		}

		$replacements = [];

		// First subgroup
		foreach ($matches[1] as $path) {
			if (isset($replacements[$path])) {
				// already retrieved
				continue;
			}

			$value = Arr::get($data, $path, self::VALUE_NOT_EXISTS);

			if ($value === self::VALUE_NOT_EXISTS) {
				throw new \RuntimeException("Value at $path does not exist."); // @todo is this the desired behavior?
			}

			$subject = str_replace(self::TOKEN_START . $path . self::TOKEN_END, $value, $subject);
		}

		return $subject;
	}
}
