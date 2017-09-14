<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Illuminate\Database\Eloquent\Model;

class LoggingTestUser extends Model
{
	public $timestamps = false;
	protected $table = 'users';
}