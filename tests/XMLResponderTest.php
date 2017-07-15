<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\XMLResponder;

class XMLResponderTest extends TestCase
{
	public function testItCanSetStatusCode()
	{
		$responder = new XMLResponder;
		$responder->setStatusCode(506);

		$this->assertSame(506, $responder->getResponse()->getStatusCode());
	}

	public function testItCanAddHeaders()
	{
		$responder = new XMLResponder;
		$responder->addHeaders([
			'X-Foo' => 'Fighters'
		]);

		$this->assertSame('Fighters', $responder->getResponse()->headers->get('X-Foo'));
	}

	public function testItCanSetData()
	{
		$responder = new XMLResponder;
		$responder->setData([
			'foo' => 'bar'
		]);

		$this->assertSame("<?xml version=\"1.0\"?>\n<root><foo>bar</foo></root>", $responder->getResponse()->getContent());
		$this->assertSame('application/xml', $responder->getResponse()->headers->get('Content-Type'));
	}
}
