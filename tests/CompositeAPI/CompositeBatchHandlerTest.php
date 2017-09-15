<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Handler\CompositeBatchHandler;
use Fuzz\ApiServer\CompositeAPI\Handler\CompositeChainedHandler;
use Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest;
use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleRequest;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeBatchHandlerTest extends AppTestCase
{
	public function testOnSampleAPI()
	{
		$handler = new CompositeBatchHandler('https://api-dev.ecall.otis.com/');

		// @todo add factory
		$request = (new CompositeGuzzleRequest)->setURI('/1.0/systems/search')
			->setHeaders(['Authorization' => ['Bearer SjqhnaC9ib2CGNHjHsz2H9xOIzP8AWBrI9sUxkSS']])
			->setMethod('GET');

		$handler->addRequest($request);

		$request = (new CompositeGuzzleRequest)->setURI('/1.0/systems/search?filters[id]==1')
			->setHeaders(['Authorization' => ['Bearer SjqhnaC9ib2CGNHjHsz2H9xOIzP8AWBrI9sUxkSS']])
			->setMethod('GET');

		$handler->addRequest($request);

		for ($i = 2; $i < 10; $i++) {
			$request = (new CompositeGuzzleRequest)->setURI("/1.0/systems/search?filters[id]==$i")
				->setHeaders(['Authorization' => ['Bearer SjqhnaC9ib2CGNHjHsz2H9xOIzP8AWBrI9sUxkSS']])
				->setMethod('GET');

			$handler->addRequest($request);
		}

		$handler->run();

		$responses = $handler->getResponses();

		$foo = 'bar';
	}

	public function testChainOnSampleAPI()
	{
		$handler = new CompositeChainedHandler('https://api-dev.ecall.otis.com/');

		// @todo add factory
		$request = (new CompositeChainedRequest)->setReferenceId('systems_search')
			->setURI('/1.0/systems/search')
			->setHeaders(['Authorization' => ['Bearer UuE5pl9jlRM3aUqpQStI2aM8zW0e8nH8ckC5cGQU']])
			->setMethod('GET');

		$handler->addRequest($request);

		$request = (new CompositeChainedRequest)->setReferenceId('get_systems')
			->setURI('/1.0/systems/search?filters[id]==${systems_search.data.0.id}')
			->setHeaders(['Authorization' => ['Bearer UuE5pl9jlRM3aUqpQStI2aM8zW0e8nH8ckC5cGQU']])
			->setMethod('GET');

		$handler->addRequest($request);

		$request = (new CompositeChainedRequest)->setReferenceId('get_single_system')
			->setURI('/1.0/systems/${get_systems.data.0.id}')
			->setHeaders(['Authorization' => ['Bearer UuE5pl9jlRM3aUqpQStI2aM8zW0e8nH8ckC5cGQU']])
			->setMethod('GET');

		$handler->addRequest($request);

		$handler->run();

		$responses = $handler->getResponses();

		$foo = 'bar';
	}
}
