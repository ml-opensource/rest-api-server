<?php

namespace Fuzz\ApiServer\Validation;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait ValidatesInput
{
	/**
	 * Validate the given request with the given rules.
	 *
	 * @param array  $input
	 * @param  array $rules
	 * @param  array $messages
	 * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
	 */
	public function validate(array $input, array $rules, array $messages = [])
	{
		$validator = $this->getValidationFactory()->make($input, $rules, $messages);

		if ($validator->fails()) {
			throw new BadRequestHttpException('Request validation failed, see supporting documentation for information on properly formatting the request.');
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
