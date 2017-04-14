<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\JsonResponder;

class JsonResponderTest extends TestCase
{
	public function testItCanSetStatusCode()
	{
		$responder = new JsonResponder;
		$responder->setStatusCode(506);

		$this->assertSame(506, $responder->getResponse()->getStatusCode());
	}

	public function testItCanAddHeaders()
	{
		$responder = new JsonResponder;
		$responder->addHeaders([
			'X-Foo' => 'Fighters'
		]);

		$this->assertSame('Fighters', $responder->getResponse()->headers->get('X-Foo'));
	}

	public function testItCanSetData()
	{
		$responder = new JsonResponder;
		$responder->setData([
			'foo' => 'bar'
		]);

		$this->assertSame(json_encode([
			'foo' => 'bar'
		]), $responder->getResponse()->getContent());
	}
}
