<?php

namespace Fuzz\ApiServer\Logging;

use Fuzz\ApiServer\Logging\Contracts\ActionLogModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ActionLog
 *
 * @package Fuzz\ApiServer\Logging
 */
class ActionLog extends Model implements ActionLogModel
{
	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [ // @todo update field names
		'created_at'     => 'datetime',
		'updated_at'     => 'datetime',
		'id'             => 'integer',
		'user_id'        => 'integer',
		'content_type'   => 'string',
		'content_target' => 'string',
		'action'         => 'string',
		'timestamp'      => 'datetime',
		'success'        => 'boolean',
		'error'          => 'string',
		'ip'             => 'string',
		'meta'           => 'array',
	];
}
