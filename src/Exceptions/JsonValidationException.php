<?php

namespace Fuzz\ApiServer\Exceptions;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException as BaseValidationException;
use Symfony\Component\HttpFoundation\Response;


class JsonValidationException extends BaseValidationException
{
	/**
	 * Validation Messages for each field.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * User facing messages.
	 *
	 * @var array
	 */
	public $userMessage;

	/**
	 * The HTTP Status Code.
	 *
	 * @var int
	 */
	public $statusCode;

	/**
	 * Create a new exception instance.
	 *
	 * @param  Validator $validator
	 * @param  Response  $response
	 */
	public function __construct(Validator $validator, Response $response)
	{
		parent::__construct($validator, $response);

		$this->data        = $validator->errors()->getMessages();
		$this->userMessage = $this->data;
		$this->statusCode  = $this->response->getStatusCode();
	}

	/**
	 * Validation data.
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Validation data that can be user facing.
	 *
	 * @return array
	 */
	public function getUserMessage(): array
	{
		return $this->userMessage;
	}

	/**
	 * Get the Status Code from the response.
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}
}
