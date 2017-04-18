<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\BaseResponder;

class BaseResponderTest extends TestCase
{
	public function testItCanSetStatusCode()
	{
		$responder = new BaseResponder;
		$responder->setStatusCode(506);

		$this->assertSame(506, $responder->getResponse()->getStatusCode());
	}

	public function testItCanAddHeaders()
	{
		$responder = new BaseResponder;
		$responder->addHeaders([
			'X-Foo' => 'Fighters'
		]);

		$this->assertSame('Fighters', $responder->getResponse()->headers->get('X-Foo'));
	}

	public function testItCanSetData()
	{
		$responder = new BaseResponder;
		$responder->setData('foo');

		$this->assertSame('foo', $responder->getResponse()->getContent());
	}
}
