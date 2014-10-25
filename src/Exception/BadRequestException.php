<?php

namespace Fuzz\ApiServer\Exception;

class BadRequestException extends HttpException
{
	const ERROR_CODE  = 'E_BAD_REQUEST';
	const STATUS_CODE = 400;
}
