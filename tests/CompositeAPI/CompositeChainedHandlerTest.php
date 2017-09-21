<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use Fuzz\ApiServer\CompositeAPI\Handler\CompositeChainedHandler;
use Fuzz\ApiServer\Tests\AppTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;

class CompositeChainedHandlerTest extends AppTestCase
{
	public function testItCanGetAndSetClient()
	{
		$client = Mockery::mock(Client::class);
		$handler = new CompositeChainedHandler('https://some.api.com/');

		$this->assertTrue($handler->getClient() instanceof Client);

		$handler->setClient($client);

		$this->assertSame($client, $handler->getClient());
	}

	public function testItRunsRequests()
	{
		$mock = new MockHandler([
			new Response(200, ['Content-Type' => ['application/json'],], json_encode([
				'id' => 1,
				'request' => 'one',
				'author' => [
					'id' => 90,
				],
				'related_posts' => [
					['id' => 56],
					['id' => 99],
				],
			])),
			new Response(201, ['Content-Type' => ['application/json'],], json_encode([
				'id' => 2,
				'request' => 'two'
			])),
		]);

		$handler = HandlerStack::create($mock);
		$client  = new Client(['handler' => $handler]);
		$handler = new CompositeChainedHandler('https://some.api.com/');
		$handler->setClient($client);

		$req_one = Mockery::mock(CompositeRequest::class);
		$req_two = Mockery::mock(CompositeRequest::class);

		$req_one->shouldReceive('setNext')->with($req_two)->once();

		$handler->addRequest($req_one);
		$handler->addRequest($req_two);

		$req_one->shouldReceive('referenceId')->once()->andReturn('post');
		$req_one->shouldReceive('getHeaders')->once()->andReturn([]);
		$req_one->shouldReceive('getURI')->times(3)->andReturn('/foo/bar/baz');
		$req_one->shouldReceive('setURI')->with('/foo/bar/baz')->once();
		$req_one->shouldReceive('hasContent')->twice()->andReturn(true);
		$req_one->shouldReceive('getContent')->twice()->andReturn('{"foo": "bar"}');
		$req_one->shouldReceive('setContent')->with('{"foo": "bar"}')->once();
		$req_one->shouldReceive('getMethod')->once()->andReturn('get');
		$req_one->shouldReceive('next')->once()->andReturn($req_two);

		$req_two->shouldReceive('referenceId')->once()->andReturn('user');
		$req_two->shouldReceive('getHeaders')->once()->andReturn([]);
		$req_two->shouldReceive('getURI')->times(3)->andReturn('/foo/bar/baz/%{post.author.id}');
		$req_two->shouldReceive('setURI')->with('/foo/bar/baz/90')->once(); // should set author ID in URI
		$req_two->shouldReceive('hasContent')->twice()->andReturn(true);
		$req_two->shouldReceive('getContent')->twice()->andReturn('{"foo": "bar", "baz": "%{post.request}"}');
		$req_two->shouldReceive('setContent')->with('{"foo": "bar", "baz": "one"}')->once(); // should set param in body
		$req_two->shouldReceive('getMethod')->once()->andReturn('post');
		$req_two->shouldReceive('next')->once()->andReturn(null);

		$handler->run();

		$responses = $handler->getResponses();

		$this->assertSame(2, count($responses));

		$this->assertSame([
			'post' => [
				'status'  => 200,
				'uri'     => '/foo/bar/baz',
				'headers' => [
					'Content-Type' => [
						0 => 'application/json',
					],
				],
				'body'    => [
					'id' => 1,
					'request' => 'one',
					'author' => [
						'id' => 90,
					],
					'related_posts' => [
						['id' => 56],
						['id' => 99],
					],
				],
			],
			'user' => [
				'status'  => 201,
				'uri'     => '/foo/bar/baz/%{post.author.id}',
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
