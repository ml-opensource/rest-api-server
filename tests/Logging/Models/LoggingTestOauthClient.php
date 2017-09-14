<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Illuminate\Database\Eloquent\Model;

class LoggingTestOauthClient extends Model
{
	public $timestamps = false;
	protected $table = 'oauth_clients';
}