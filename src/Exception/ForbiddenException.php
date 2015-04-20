<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends OAuthException
{
	/**
	 * @inheritDoc
	 */
	const DEFAULT_MESSAGE = 'Access denied.';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_ERROR_TYPE = 'forbidden';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_FORBIDDEN;
}
