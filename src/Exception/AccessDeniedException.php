<?php

namespace Fuzz\ApiServer\Exception;

class AccessDeniedException extends HttpException
{
	const ERROR_CODE  = 'E_ACCESS_DENIED';
	const STATUS_CODE = 403;
}
