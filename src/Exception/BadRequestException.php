<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends OAuthException
{
	/**
	 * @inheritDoc
	 */
	const DEFAULT_MESSAGE = 'Unable to fulfill this request.';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_ERROR_TYPE = 'bad_request';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_BAD_REQUEST;
}
