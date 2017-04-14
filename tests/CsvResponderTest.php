<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\CsvResponder;

class CsvResponderTest extends TestCase
{
	public function testItCanSetStatusCode()
	{
		$responder = new CsvResponder;
		$responder->setStatusCode(506);

		$this->assertSame(506, $responder->getResponse()->getStatusCode());
	}

	public function testItCanAddHeaders()
	{
		$responder = new CsvResponder;
		$responder->addHeaders([
			'X-Foo' => 'Fighters'
		]);

		$this->assertSame('Fighters', $responder->getResponse()->headers->get('X-Foo'));
	}

	public function testItCanSetData()
	{
		$responder = new CsvResponder;
		$responder->setData([
			[
				'foo' => 'bar',
				'baz' => 'bat',
			]
		]);

		$this->assertSame("foo,baz\n\"bar\",\"bat\"", $responder->getResponse()->getContent());
		$this->assertSame('text/csv', $responder->getResponse()->headers->get('Content-Type'));
		$this->assertTrue(is_string($responder->getResponse()->headers->get('Content-Disposition')));
	}
}
