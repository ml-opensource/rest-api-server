<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Http\CompositeGuzzleRequest;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeGuzzleRequestTest extends AppTestCase
{
	public function testGettersAndSetters()
	{
		$request = new CompositeGuzzleRequest;

		$this->assertFalse($request->hasContent());
		$request->setContent('{"foo": "bar"}');
		$this->assertTrue($request->hasContent());
		$this->assertSame('{"foo": "bar"}', $request->getContent());

		$request->setHeaders([
			'Content-Type' => ['application/json'],
		]);
		$this->assertSame([
			'Content-Type' => ['application/json'],
		], $request->getHeaders());

		$request->setURI('/foo/bar/baz');
		$this->assertSame('/foo/bar/baz', $request->getURI());
	}
}
