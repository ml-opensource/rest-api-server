<?php

namespace Fuzz\ApiServer\Exception;

class UnauthorizedException extends HttpException
{
	const ERROR_CODE  = 'E_UNAUTHORIZED';

	const STATUS_CODE = 401;
}
