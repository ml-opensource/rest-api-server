<?php

namespace Fuzz\ApiServer\Routing;


/**
 * Class HealthCheckController
 *
 * You can use this basic controller to implement a health check route.
 * It can also be extended and used to add additional custom health checks.
 *
 * @package Fuzz\ApiServer\Routing
 */
class HealthCheckController extends \Illuminate\Routing\Controller
{
	/**
	 * Generic health check.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function ping()
	{
		return response()->json('pong', 200);
	}
}
