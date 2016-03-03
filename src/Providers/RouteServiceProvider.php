<?php

namespace Fuzz\ApiServer\Providers;

use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * Load the application routes.
	 *
	 * @return void
	 */
	protected function loadRoutes()
	{
		// Always implement the token dance
		$this->app['router']->post(
			'oauth/access_token', 'Fuzz\ApiServer\Routing\OAuthController@issueAccessToken'
		);

		parent::loadRoutes();
	}

	/**
	 * Define the routes for the application.
	 *
	 * @param  \Illuminate\Routing\Router $router
	 * @return void
	 */
	public function map(Router $router)
	{
		// Register a handy macro for registering resource routes
		$router->macro('restful', function ($model_name, $resource_controller = 'ResourceController') use ($router) {
			$alias = Str::lower(Str::snake(Str::plural(class_basename($model_name)), '-'));

			$router->resource($alias, $resource_controller, [
				'only' => [
					'index',
					'store',
					'show',
					'update',
					'destroy',
				],
			]);
		});

		$router->group(['namespace' => $this->namespace], function ($router) {
			require app_path('Http/routes.php');
		});
	}
}
