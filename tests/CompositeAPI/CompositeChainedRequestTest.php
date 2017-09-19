<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeChainedRequestTest extends AppTestCase
{
	public function testGettersAndSetters()
	{
		$request = new CompositeChainedRequest;

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

		$request->setReferenceId('foo_request');
		$this->assertSame('foo_request', $request->referenceId());
	}

	public function testChainMethods()
	{
		$request = new CompositeChainedRequest;

		$next = new CompositeChainedRequest;

		$this->assertFalse($request->hasNext());
		$request->setNext($next);
		$this->assertTrue($request->hasNext());

		$this->assertSame($next, $request->next());
	}
}
