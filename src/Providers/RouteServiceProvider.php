<?php

namespace Fuzz\ApiServer\Providers;

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
}
