<?php

namespace Fuzz\ApiServer\Validation;

use Fuzz\ApiServer\Exception\BadRequestException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;

trait ValidatesRequests
{
	/**
	 * Validate the given request with the given rules.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  array                    $rules
	 * @param  array                    $messages
	 * @return void
	 */
	public function validate(Request $request, array $rules, array $messages = [])
	{
		$validator = $this->getValidationFactory()->make($request->all(), $rules, $messages);

		if ($validator->fails()) {
			$this->throwValidationException($request, $validator);
		}
	}

	/**
	 * Throw the failed validation exception.
	 *
	 * @param  \Illuminate\Http\Request                   $request
	 * @param  \Illuminate\Contracts\Validation\Validator $validator
	 * @return void
	 */
	protected function throwValidationException(Request $request, Validator $validator)
	{
		throw new BadRequestException('Request validation failed.', $validator->errors()->getMessages());
	}

	/**
	 * Get a validation factory instance.
	 *
	 * @return \Illuminate\Contracts\Validation\Factory
	 */
	protected function getValidationFactory()
	{
		return app('Illuminate\Contracts\Validation\Factory');
	}
}
