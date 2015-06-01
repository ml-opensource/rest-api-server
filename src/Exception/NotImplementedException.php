<?php

namespace Fuzz\ApiServer\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotImplementedException extends OAuthException
{
	/**
	 * @inheritDoc
	 */
	const DEFAULT_MESSAGE = 'This endpoint is not yet implemented.';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_ERROR_TYPE = 'not_implemented';

	/**
	 * @inheritDoc
	 */
	const DEFAULT_STATUS_CODE = Response::HTTP_NOT_IMPLEMENTED;
}
