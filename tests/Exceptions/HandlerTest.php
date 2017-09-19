<?php

namespace Tests\Exceptions;


use Exception;
use Fuzz\ApiServer\Exceptions\Handler;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\HttpException\HttpException;
use Fuzz\HttpException\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;


class HandlerTest extends AppTestCase
{
	public function testCanSerializeFuzzHttpExceptionToJsonResponse()
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

	public function testItConvertsSymfonyHttpExceptionToFuzzHttpException()
	{
		$handler      = new HandlerStub($this->app);
		$original_exception = new SymfonyHttpException(405, 'Some message');
		$err = $handler->toHttpException($original_exception);

		$this->assertTrue($err instanceof HttpException);
		$this->assertSame(405, $err->getStatusCode());
		$this->assertSame('Some message', $err->getError());
		$this->assertSame($original_exception->getHeaders(), $err->getHttpHeaders());
	}

	public function testItConvertsModelNotFoundExceptionToFuzzHttpException()
	{
		$handler      = new HandlerStub($this->app);
		$original_exception = new ModelNotFoundException;
		$original_exception->setModel(ModelNotFoundException::class);
		$err = $handler->toHttpException(new ModelNotFoundException);

		$this->assertTrue($err instanceof NotFoundHttpException);
		$this->assertSame('Not Found!', $err->getUserTitle());
		$this->assertSame('Sorry, seems we can\'t find what you\'re looking for.', $err->getUserMessage());
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
