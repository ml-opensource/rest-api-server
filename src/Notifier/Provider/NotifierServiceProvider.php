<?php

namespace Fuzz\ApiServer\Notifier\Provider;

use Fuzz\ApiServer\Notifier\Facades\Notifier;
use Fuzz\ApiServer\Notifier\NotifierEngine;
use Illuminate\Support\ServiceProvider;

class NotifierServiceProvider extends ServiceProvider
{
	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$config_file = realpath(__DIR__ . '/../config/notifier.php');
		$this->publishes([
			$config_file => config_path('notifier.php'),
		], 'config');

		$this->loadViewsFrom(__DIR__ . '/../Handlers/Email/Views', 'email_notifier');
	}

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(Notifier::class, function () {
			return new NotifierEngine(config('notifier'));
		});
	}
}
