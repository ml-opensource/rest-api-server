<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends OAuthException
{
	/**
	 * @inheritDoc
	 */
	const DEFAULT_MESSAGE = 'The resource was not found.';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_ERROR_TYPE = 'not_found';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_NOT_FOUND;
}
