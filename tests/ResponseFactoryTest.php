<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\CsvResponder;
use Fuzz\ApiServer\Response\JsonResponder;
use Fuzz\ApiServer\Response\ResponseFactory;
use InvalidArgumentException;
use LogicException;

class ResponseFactoryTest extends TestCase
{
	public function testItCanSetResponderForFormat()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$factory->setResponderForFormat('csv', CsvResponder::class);

		$this->assertTrue($factory->getResponderForFormat('csv') instanceof CsvResponder);
	}

	public function testItThrowsInvalidArgumentExceptionIfSettingANonResponder()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Fuzz\ApiServer\Response\ResponseFactory is not valid responder.');
		$factory->setResponderForFormat('csv', ResponseFactory::class);
	}

	public function testItThrowsLogicExceptionIfNoResponderIsSet()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('No responder has been set.');
		$factory->getResponder();
	}

	public function testItThrowsInvalidArgumentExceptionIfFormatDoesNotHaveResponder()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('csv is not a valid response type.');
		$factory->getResponderForFormat('csv');
	}

	public function testItThrowsInvalidArgumentExceptionIfResponseFormatDoesNotExist()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('csv is not a valid response type.');
		$factory->setResponseFormat('csv');
	}

	public function testItThrowsLogicExceptionIfNoResponderIsSetWhenMakingRequest()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('No responder has been set.');
		$response = $factory->makeResponse(['foo' => 'bar'], 200, ['baz' => 'bar']);
	}

	public function testItSetsResponseFormat()
	{
		$factory = new ResponseFactory([
			'json' => JsonResponder::class,
		]);

		$factory->setResponseFormat('json');

		$response = $factory->makeResponse(['foo' => 'bar'], 200, ['baz' => 'bar']);

		$this->assertSame('{"foo":"bar"}', $response->getContent());
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('bar', $response->headers->get('baz'));
	}
}
