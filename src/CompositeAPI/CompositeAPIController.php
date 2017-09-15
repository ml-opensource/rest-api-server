<?php

namespace Fuzz\ApiServer\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Handler\CompositeBatchHandler;
use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArbitraryStringThrottler
 *
 * ArbitraryStringThrottler throttles by an arbitrary string key.
 *
 * @package Fuzz\ApiServer\Throttling
 */
class CompositeAPIController extends Controller
{
	const PARALLEL_VALIDATION = [
		'*.method' => 'required|string|in:get,put,post,patch,delete',
		'*.body'   => 'array',
	];

	public function parallel(Request $request): Response
	{
		$input = $request->all();
		$validator = Validator::make($input, self::PARALLEL_VALIDATION);

		$headers = $request->headers->all();
		$handler = new CompositeBatchHandler('https://api-dev.ecall.otis.com/');

		foreach ($input as $uri => $options) {
			$sub_request = (new CompositeGuzzleRequest)->setURI($uri)
				->setHeaders($headers)
				->setMethod($options['method']);

			if (isset($options['body'])) {
				$sub_request->setContent(json_encode($options['body']));
			}

			$handler->addRequest($sub_request);
		}

		$responses = $handler->run()->getResponses();

		$foo = 'bar'
	}
}