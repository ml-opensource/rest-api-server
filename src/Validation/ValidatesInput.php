<?php

namespace Fuzz\ApiServer\Validation;

use Fuzz\ApiServer\Exception\BadRequestException;

trait ValidatesInput
{
	/**
	 * Validate the given request with the given rules.
	 *
	 * @param array  $input
	 * @param  array $rules
	 * @param  array $messages
	 * @throws \Fuzz\ApiServer\Exception\BadRequestException
	 */
	public function validate(array $input, array $rules, array $messages = [])
	{
		$validator = $this->getValidationFactory()->make($input, $rules, $messages);

		if ($validator->fails()) {
			throw new BadRequestException('Request validation failed.', $validator->errors()->getMessages());
		}
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
