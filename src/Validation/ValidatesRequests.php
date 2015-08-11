<?php

namespace Fuzz\ApiServer\Validation;

use Illuminate\Validation\Validator;
use Fuzz\ApiServer\Exception\BadRequestException;

trait ValidatesRequests
{
	/**
	 * Validate the given request with the given rules.
	 *
	 * @param  array $request
	 * @param  array $rules
	 * @param  array $messages
	 * @return void
	 */
	public function validate(array $input, array $rules, array $messages = [])
	{
		$validator = $this->getValidationFactory()->make($input, $rules, $messages);

		if ($validator->fails()) {
			$this->throwValidationException($validator);
		}
	}

	/**
	 * Throw the failed validation exception.
	 *
	 * @param  \Illuminate\Contracts\Validation\Validator $validator
	 * @return void
	 */
	protected function throwValidationException(Validator $validator)
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
