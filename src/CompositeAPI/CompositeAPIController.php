<?php

namespace Fuzz\ApiServer\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Handler\CompositeBatchHandler;
use Fuzz\ApiServer\CompositeAPI\Handler\CompositeChainedHandler;
use Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest;
use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleRequest;
use Fuzz\HttpException\BadRequestHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CompositeAPIController
 *
 * @package Fuzz\ApiServer\Throttling
 */
class CompositeAPIController extends Controller
{
	/**
	 * Validation rules
	 *
	 * @const array
	 */
	const PARALLEL_VALIDATION = [
		'*.method' => 'required|string|in:get,put,post,patch,delete',
		'*.body'   => 'array',
	];
	const CHAIN_VALIDATION    = [
		'*.method' => 'required|string|in:get,put,post,patch,delete',
		'*.body'   => 'array',
		'*.ref'    => 'string|distinct',
	];

	/**
	 * Run API requests in parallel
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function parallel(Request $request): Response
	{
		$input     = $request->all();
		$validator = Validator::make($input, self::PARALLEL_VALIDATION);

		if ($validator->fails()) {
			throw new BadRequestHttpException('bad_request', $validator->errors()->all());
		}

		$headers = $this->cleanRequestHeaders($request->headers);

		$handler = new CompositeBatchHandler(config('app.url'));

		foreach ($input as $uri => $options) {
			$sub_request = (new CompositeGuzzleRequest)->setURI($uri)->setHeaders($headers)
				->setMethod($options['method']);

			if (isset($options['body'])) {
				$sub_request->setContent(json_encode($options['body']));
			}

			$handler->addRequest($sub_request);
		}

		$responses = $handler->run()->readResponses();

		return new JsonResponse($responses);
	}

	/**
	 * Run API requests in a chain
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function chain(Request $request): Response
	{
		$input     = $request->all();
		$validator = Validator::make($input, self::CHAIN_VALIDATION);

		if ($validator->fails()) {
			throw new BadRequestHttpException('bad_request', $validator->errors()->all());
		}

		$headers = $this->cleanRequestHeaders($request->headers);

		$handler = new CompositeChainedHandler(config('app.url'));

		foreach ($input as $uri => $options) {
			$sub_request = (new CompositeChainedRequest)->setReferenceId($options['ref'])->setURI($uri)
				->setHeaders($headers)->setMethod($options['method']);

			if (isset($options['body'])) {
				$sub_request->setContent(json_encode($options['body']));
			}

			$handler->addRequest($sub_request);
		}

		$responses = $handler->run()->readResponses();

		return new JsonResponse($responses);
	}

	/**
	 * Clean out headers from the request and return a set that can be passed up to the proxy
	 *
	 * @param \Symfony\Component\HttpFoundation\HeaderBag $header_bag
	 *
	 * @return array
	 */
	private function cleanRequestHeaders(HeaderBag $header_bag): array
	{
		$headers = $header_bag->all();
		unset($headers['content-length']);

		return $headers;
	}
}