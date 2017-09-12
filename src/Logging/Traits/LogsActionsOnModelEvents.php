<?php

namespace Fuzz\ApiServer\Traits;

use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Logging\Contracts\ActionLoggerInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait LogsActionsOnModelEvents
 *
 * LogsActionsOnModelEvents contains all model event callbacks which push to the action logger.
 *
 * @package Fuzz\ApiServer\Traits
 */
trait LogsModelEvents
{
	/**
	 * Bind event handlers to model
	 *
	 * https://github.com/laravel/framework/blob/5.5/src/Illuminate/Database/Eloquent/Model.php#L193
	 *
	 * @return void
	 */
	protected static function bootLoggableActions()
	{
		/**
		 * Log on model creates
		 */
		self::created(function (Model $model) {
			self::logAction(ActionLoggerInterface::STORE_ACTION, $model);

			// Continue creating
			return true;
		});

		/**
		 * Log on model updates
		 */
		self::updating(function (Model $model) {
			self::logAction(ActionLoggerInterface::UPDATE_ACTION, $model);

			// Continue updating
			return true;
		});

		/**
		 * Log on model deletes
		 */
		self::deleting(function (Model $model) {
			self::logAction(ActionLoggerInterface::DESTROY_ACTION, $model);

			// Continue deleting
			return true;
		});
	}

	/**
	 * Log a model action event
	 *
	 * @param string                              $action
	 * @param \Illuminate\Database\Eloquent\Model $model
	 */
	protected static function logAction(string $action, Model $model)
	{
		// Don't attempt to add messages to queue if logging is disabled
		if (! ActionLogger::isEnabled()) {
			return;
		}

		ActionLogger::log($action, class_basename($model), (string) $model->id);
	}
}