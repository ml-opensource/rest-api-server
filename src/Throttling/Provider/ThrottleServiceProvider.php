<?php

namespace Fuzz\ApiServer\Throttling\Provider;

use Illuminate\Support\ServiceProvider;

class ThrottleServiceProvider extends ServiceProvider
{
	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$config_file = realpath(__DIR__ . '/../config/throttling.php');
		$this->publishes([
			$config_file => config_path('throttling.php'),
		], 'config');
	}

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{

	}
}
