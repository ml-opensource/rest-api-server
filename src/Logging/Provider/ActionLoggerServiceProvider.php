<?php

namespace Fuzz\ApiServer\Logging\Provider;

use Fuzz\ApiServer\Logging\ActionLogEngineManager;
use Fuzz\ApiServer\Logging\Facades\ActionLogger;
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
			realpath(__DIR__ . '/../migrations') => database_path('/migrations'),
		], 'migrations');
	}

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(ActionLogEngineManager::class, function ($app) {
			return new ActionLogEngineManager($app);
		});

		$this->app->singleton(ActionLogger::class, function ($app) {
			// First, we will create the ActionLog manager which is responsible for the
			// creation of the various action log drivers when they are needed by the
			// application instance, and will resolve them on a lazy load basis.
			/** @var ActionLogEngineManager $manager */
			$manager = $app[ActionLogEngineManager::class];

			return $manager->driver(config('action_log.driver'));
		});
	}
}
