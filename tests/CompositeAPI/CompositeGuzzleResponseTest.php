<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleResponse;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeGuzzleResponseTest extends AppTestCase
{
	public function testGettersAndSetters()
	{
		$response = new CompositeGuzzleResponse;

		$response->setHeaders([
			'Content-Type' => ['application/json'],
		]);
		$this->assertSame([
			'Content-Type' => ['application/json'],
		], $response->getHeaders());

		$response->setContent('{"foo": "bar"}');
		$this->assertSame('{"foo": "bar"}', $response->getContent());
		$this->assertSame(json_decode('{"foo": "bar"}', true), $response->getSerializedContent());

		$response->setURI('/foo/bar/baz');
		$this->assertSame('/foo/bar/baz', $response->getURI());

		$response->setStatusCode(206);
		$this->assertSame(206, $response->getStatusCode());

		$this->assertSame([
			'status'  => 206,
			'uri'     => '/foo/bar/baz',
			'headers' => ['Content-Type' => ['application/json'],],
			'body'    => json_decode('{"foo": "bar"}', true),
		], $response->toArray());

		$this->assertSame(json_encode([
			'status'  => 206,
			'uri'     => '/foo/bar/baz',
			'headers' => ['Content-Type' => ['application/json'],],
			'body'    => json_decode('{"foo": "bar"}', true),
		]), $response->toJson());
	}

	public function testItThrowsRuntimeExceptionIfNotJsonResponseButTriesToSerialize()
	{
		$response = new CompositeGuzzleResponse;

		$response->setContent('{"foo": "bar"}');
		$response->setHeaders([
			'Content-Type' => ['not_application/json'],
		]);

		try {
			$response->getSerializedContent();

			$this->fail('Exception should be thrown.');
		} catch (\RuntimeException $e) {
			// pass
		}

		$response->setHeaders([
			'Content-Type' => ['application/json'],
		]);

		$this->assertSame(json_decode('{"foo": "bar"}', true), $response->getSerializedContent());
	}
}
