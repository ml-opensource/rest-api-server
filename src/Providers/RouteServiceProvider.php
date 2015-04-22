<?php

namespace Fuzz\ApiServer\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;

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
			'oauth/access_token', function () {
				return new JsonResponse(
					$this->app['oauth2-server.authorizer']->issueAccessToken()
				);
			}
		);

		parent::loadRoutes();
	}
}
