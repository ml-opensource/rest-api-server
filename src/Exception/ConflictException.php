<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;

class ConflictException extends OAuthException
{
	/**
	 * @inheritDoc
	 */
	const DEFAULT_MESSAGE = 'Unable to fulfill this request due to conflict.';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_ERROR_TYPE = 'conflict';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_CONFLICT;
}
