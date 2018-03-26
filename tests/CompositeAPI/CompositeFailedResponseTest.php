<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Http\FailedResponse;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeFailedResponseTest extends AppTestCase
{
	public function testItThrowsLogicExceptionsOnSetters()
	{
		$response = new FailedResponse('some_reason');

		$this->expectException(\LogicException::class);
		$response->setContent('foo');

		$this->expectException(\LogicException::class);
		$response->setStatusCode(200);

		$this->expectException(\LogicException::class);
		$response->setHeaders(['foo']);
	}

	public function testItSerializesErrorResponse()
	{
		$response = new FailedResponse('some_reason');

		$this->assertSame([
			'error'         => 'service_unavailable',
			'error_message' => 'some_reason',
		], $response->getErrorResponse());

		$this->assertSame([
			'error'         => 'service_unavailable',
			'error_message' => 'some_reason',
		], $response->toArray());

		$this->assertSame(json_encode([
			'error'         => 'service_unavailable',
			'error_message' => 'some_reason',
		]), $response->toJson());
	}
}
