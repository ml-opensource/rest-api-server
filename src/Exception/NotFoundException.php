<?php

namespace Fuzz\ApiServer\Exception;

class NotFoundException extends HttpException
{
	const ERROR_CODE  = 'E_NOT_FOUND';
	const STATUS_CODE = 404;
}
