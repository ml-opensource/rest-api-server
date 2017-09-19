<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use Fuzz\ApiServer\CompositeAPI\Handler\CompositeBatchHandler;
use Fuzz\ApiServer\Tests\AppTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;

class CompositeBatchHandlerTest extends AppTestCase
{
	public function testItCanGetAndSetClient()
	{
		$client = Mockery::mock(Client::class);
		$handler = new CompositeBatchHandler('https://some.api.com/');

		$this->assertTrue($handler->getClient() instanceof Client);

		$handler->setClient($client);

		$this->assertSame($client, $handler->getClient());
	}

	public function testItRunsRequestsAsync()
	{
		$mock = new MockHandler([
			new Response(201, ['Content-Type' => ['application/json'],], json_encode([
				'id' => 1,
				'request' => 'one'
			])),
			new Response(200, ['Content-Type' => ['application/json'],], json_encode([
				'id' => 2,
				'request' => 'two'
			])),
		]);

		$handler = HandlerStack::create($mock);
		$client  = new Client(['handler' => $handler]);
		$handler = new CompositeBatchHandler('https://some.api.com/');
		$handler->setClient($client);

		$req_one = Mockery::mock(CompositeRequest::class);
		$req_two = Mockery::mock(CompositeRequest::class);
		$handler->addRequest($req_one);
		$handler->addRequest($req_two);

		$req_one->shouldReceive('hasContent')->once()->andReturn(true);
		$req_one->shouldReceive('getContent')->once()->andReturn('{"foo": "bar"}');
		$req_one->shouldReceive('getMethod')->once()->andReturn('post');
		$req_one->shouldReceive('getURI')->twice()->andReturn('/foo/bar/baz');
		$req_one->shouldReceive('getHeaders')->once()->andReturn(['Content-Type' => ['application/json'],]);

		$req_two->shouldReceive('hasContent')->once()->andReturn(false);
		$req_two->shouldReceive('getContent')->never();
		$req_two->shouldReceive('getMethod')->once()->andReturn('get');
		$req_two->shouldReceive('getURI')->twice()->andReturn('/foo/var/baz/2');
		$req_two->shouldReceive('getHeaders')->once()->andReturn(['Content-Type' => ['application/json'],]);

		$handler->run();

		$responses = $handler->getResponses();

		$this->assertSame(2, count($responses));

		$this->assertSame([
			'/foo/bar/baz' => [
				'status'  => 201,
				'uri'     => '/foo/bar/baz',
				'headers' => [
					'Content-Type' => [
						0 => 'application/json',
					],
				],
				'body'    => [
					'id'      => 1,
					'request' => 'one',
				],
			],
			'/foo/var/baz/2' => [
				'status'  => 200,
				'uri'     => '/foo/var/baz/2',
				'headers' => [
					'Content-Type' => [
						0 => 'application/json',
					],
				],
				'body'    => [
					'id'      => 2,
					'request' => 'two',
				],
			],
		], $handler->readResponses());
	}
}
