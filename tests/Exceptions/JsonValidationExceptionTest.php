<?php

namespace Tests\Exceptions;


use Fuzz\ApiServer\Exceptions\JsonValidationException;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\JsonResponse;


class JsonValidationExceptionTest extends AppTestCase
{
	/**
	 * Tests the data from the exception can be received
	 */
	public function testCanGetData()
	{
		$dataShouldBe = [
			'password' => ['The password field is required.'],
		];

		$err = $this->jsonValidationException();

		$this->assertEquals($dataShouldBe, $err->getData());
	}

	/**
	 * Tests the user message from the exception can be received.
	 */
	public function testCanGetUserMessage()
	{
		$userMessageShouldBe = [
			'password' => ['The password field is required.'],
		];

		$err = $this->jsonValidationException();

		$this->assertEquals($userMessageShouldBe, $err->getUserMessage());
	}

	/**
	 * Tests the status code from the exception can be received.
	 */
	public function testCanGetStatusCode()
	{
		$statusCodeShouldBe = 400;

		$err = $this->jsonValidationException();

		$this->assertEquals($statusCodeShouldBe, $err->getStatusCode());
	}

	/**
	 * Creates a JsonValidationException to use for other tests.
	 *
	 * @return JsonValidationException
	 */
	protected function jsonValidationException()
	{
		$input            = [
			'email' => 'user@fuzzproductions.com',
		];
		$rules            = [
			'email'    => 'required',
			'password' => 'required',
		];
		$messages         = [];
		$customAttributes = [];
		$validator        = app(Factory::class)->make($input, $rules, $messages, $customAttributes);

		return new JsonValidationException($validator, new JsonResponse($validator->errors()->getMessages(), 400));
	}
}
