<?php

namespace Fuzz\ApiServer\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Fuzz\ApiServer\Response\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ApiServerServiceProvider extends ServiceProvider
{
	/**
	* Register any other events for your application.
	*
	* @param  \Illuminate\Contracts\Events\Dispatcher $events
	* @return void
	*/
	public function boot(DispatcherContract $events)
	{
		parent::boot($events);

		$config_file = realpath(__DIR__ . '/../config/api.php');
		$this->publishes(
			[
				$config_file => config_path('api.php'),
			]
		);
	}

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(ResponseFactory::class, function ($app) {
			return new ResponseFactory(config('api.responders'));
		});
	}
}
