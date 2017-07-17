<?php

namespace Tests\Exceptions;


use Exception;
use Fuzz\ApiServer\Exceptions\Handler;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\HttpException\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;


class HandlerTest extends AppTestCase
{
	public function testCanToJsonResponse()
	{
		$handler = new HandlerStub($this->app);

		$this->assertInstanceOf(JsonResponse::class, $handler->toJsonResponse(new HttpException()));
	}

	public function testCanGetResponseDataFromException()
	{
		$this->app['config']->set('app.debug', false);
		$handler      = new HandlerStub($this->app);
		$responseData = $handler->getResponseDataFromException(new HttpException());

		$this->assertArrayHasKey('error', $responseData);
		$this->assertArrayHasKey('error_description', $responseData);
		$this->assertArrayHasKey('error_data', $responseData);
		$this->assertArrayHasKey('user_title', $responseData);
		$this->assertArrayHasKey('user_message', $responseData);
		$this->assertArrayNotHasKey('debug', $responseData);
	}

	public function testCanGetResponseDataFromExceptionWithDebugExtras()
	{
		$this->app['config']->set('app.debug', true);
		$handler      = new HandlerStub($this->app);
		$responseData = $handler->getResponseDataFromException(new HttpException());

		$this->assertArrayHasKey('error', $responseData);
		$this->assertArrayHasKey('error_description', $responseData);
		$this->assertArrayHasKey('error_data', $responseData);
		$this->assertArrayHasKey('user_title', $responseData);
		$this->assertArrayHasKey('user_message', $responseData);
		$this->assertArrayHasKey('debug', $responseData);
		$this->assertArrayHasKey('code', $responseData['debug']);
		$this->assertArrayHasKey('message', $responseData['debug']);
		$this->assertArrayHasKey('line', $responseData['debug']);
		$this->assertArrayHasKey('file', $responseData['debug']);
		$this->assertArrayHasKey('class', $responseData['debug']);
		$this->assertArrayHasKey('trace', $responseData['debug']);
	}

	public function testAppInDebugTrue()
	{
		$this->app['config']->set('app.debug', true);
		$handler      = new HandlerStub($this->app);

		$this->assertTrue($handler->appInDebug());
	}

	public function testAppInDebugFalse()
	{
		$this->app['config']->set('app.debug', false);
		$handler      = new HandlerStub($this->app);

		$this->assertFalse($handler->appInDebug());
	}
}

class HandlerStub extends Handler
{

	public function toJsonResponse(HttpException $err): JsonResponse
	{
		return parent::toJsonResponse($err);
	}

	public function getResponseDataFromException(HttpException $err): array
	{
		return parent::getResponseDataFromException($err);
	}

	public function toHttpException(Exception $err): HttpException
	{
		return parent::toHttpException($err);
	}

	public function convertFromModelNotFound(ModelNotFoundException $err): HttpException
	{
		return parent::convertFromModelNotFound($err);
	}

	public function defaultHttpException(): HttpException
	{
		return parent::defaultHttpException();
	}

	public function appInDebug(): bool
	{
		return parent::appInDebug();
	}
}
