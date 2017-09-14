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
	protected $casts = [
		'id'           => 'integer',
		'created_at'   => 'datetime',
		'user_id'      => 'integer',
		'resource'     => 'string',
		'resource_id'  => 'string',
		'action'       => 'string',
		'note'         => 'string',
		'error_status' => 'string',
		'ip'           => 'string',
		'meta'         => 'array',
	];

	/**
	 * Ignore updated at
	 *
	 * @param  mixed $value
	 *
	 * @return $this
	 */
	public function setUpdatedAt($value)
	{
		return $this;
	}
}
