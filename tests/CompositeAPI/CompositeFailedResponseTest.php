<?php

namespace Fuzz\ApiServer\Tests\CompositeAPI;

use Fuzz\ApiServer\CompositeAPI\Http\FailedResponse;
use Fuzz\ApiServer\Tests\AppTestCase;

class CompositeFailedResponseTest extends AppTestCase
{
	public function testItThrowsLogicExceptionsOnSetters()
	{
		$response = new FailedResponse('some_reason');

		try {
			$response->setContent('foo');

			$this->fail('should throw.');
		} catch (\LogicException $exception) {
			// pass
		}

		try {
			$response->setStatusCode(200);

			$this->fail('should throw.');
		} catch (\LogicException $exception) {
			// pass
		}

		try {
			$response->setHeaders(['foo']);

			$this->fail('should throw.');
		} catch (\LogicException $exception) {
			// pass
		}
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
