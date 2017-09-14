<?php

namespace Fuzz\ApiServer\Logging\Provider;

use Fuzz\ApiServer\Logging\Facades\ActionLogger;
use Fuzz\ApiServer\Logging\MySQLActionLogger;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class ActionLoggerServiceProvider extends ServiceProvider
{
	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$config_file = realpath(__DIR__ . '/../config/action_log.php');
		$this->publishes([
			$config_file => config_path('action_log.php'),
		], 'config');

		$this->publishes([
			realpath(__DIR__ . '/../migrations') => database_path('/migrations')
		], 'migrations');
	}

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(ActionLogger::class, function () {
			return new MySQLActionLogger(config('services.action_logger'), Request::instance());
		});
	}
}
