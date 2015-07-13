<?php

namespace Fuzz\ApiServer\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider;
use LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider;

class ApiServerServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerOAuthProviders();
		$this->registerCommands();
	}

	/**
	 * Add the Cors middleware to the HTTP kernel.
	 *
	 * @param \Illuminate\Contracts\Http\Kernel $kernel
	 * @return void
	 */
	public function boot(Kernel $kernel)
	{
		$kernel->pushMiddleware('Fuzz\ApiServer\Routing\CorsMiddleware');
	}

	/**
	 * Register the service providers associated with the
	 * lucadegasperi/oauth2-server-laravel package.
	 *
	 * @return void
	 */
	protected function registerOAuthProviders()
	{
		$this->app->register(new FluentStorageServiceProvider($this->app));
		$this->app->register(new OAuth2ServerServiceProvider($this->app));
	}

	/**
	 * Register the commands provided by this package.
	 *
	 * @return void
	 */
	protected function registerCommands()
	{
		$this->commands(
			[
				'Fuzz\ApiServer\Console\ApiInitializeCommand',
			]
		);
	}
}
