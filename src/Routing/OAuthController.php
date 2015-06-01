<?php

namespace Fuzz\ApiServer\Routing;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Foundation\Application;

class OAuthController extends Controller
{
	/**
	 * Issue an access token.
	 *
	 * @param \Illuminate\Contracts\Foundation\Application $app
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function issueAccessToken(Application $app)
	{
		return new JsonResponse(
			$app['oauth2-server.authorizer']->issueAccessToken()
		);
	}
}
