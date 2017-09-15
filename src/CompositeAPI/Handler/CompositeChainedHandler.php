<?php

namespace Fuzz\ApiServer\CompositeAPI\Handler;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeHandler;
use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;

/**
 * Class CompositeBatchHandler
 *
 * The CompositeBatchHandler runs parallel HTTP requests asynchronously.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Handler
 */
class CompositeChainedHandler extends CompositeBatchHandler implements CompositeHandler
{
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
			$this->parseTokensIntoRequest($current);

			$guzzle_request = $this->toGuzzleRequest($current);

			$response = $this->readResponse($this->sendRequest($guzzle_request));

			$this->responses[$current->getURI()] = $response;
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
			$request->setContent($this->parseDataIntoTokens($request->getContent(), $this->reference_data));
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
		$found = preg_match_all('/\${([^}]+)}/', $subject, $matches);

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

			$value = Arr::get($data, $path); // @todo what to do if not exists? check exists first? Have a meaningful non-null default?

			$subject = str_replace('${' . $path . '}', $value, $subject);
		}

		return $subject;
	}
}
