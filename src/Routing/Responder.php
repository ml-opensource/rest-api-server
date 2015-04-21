<?php

namespace Fuzz\ApiServer\Routing;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\Response;
use League\OAuth2\Server\Exception\OAuthException;
use Fuzz\ApiServer\Exception\OAuthException as FuzzOAuthException;

class Responder
{
	/**
	 * Send a response.
	 *
	 * @param mixed $data
	 * @param int   $status_code
	 * @param array $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	final public function send($data, $status_code, $headers)
	{
		if ($data instanceof Arrayable) {
			$data = $data->toArray();
		}

		return new JsonResponse($data, $status_code, $headers);
	}

	/**
	 * Notify the caller of failure.
	 *
	 * @param \Exception $exception
	 * @return \Illuminate\Http\JsonResponse
	 */
	final public function sendException(\Exception $exception)
	{
		/**
		 * Handle known HTTP exceptions RESTfully.
		 */
		if ($exception instanceof OAuthException) {
			$error             = $exception->errorType;
			$error_description = $exception->getMessage();
			$status_code       = $exception->httpStatusCode;
			$headers           = $exception->getHttpHeaders();

			$error_data = ($exception instanceof FuzzOAuthException) ? $exception->errorData : null;
		} else {
			/**
			 * Contextualize response with verbose information outside production.
			 *
			 * Report only "unknown" errors in production.
			 */
			$error = 'unknown';

			if (Config::get('app.debug')) {
				$error_description = [
					'message' => $exception->getMessage(),
					'class'   => get_class($exception),
					'file'    => $exception->getFile(),
					'line'    => $exception->getLine(),
				];
			}

			$status_code = Response::HTTP_INTERNAL_SERVER_ERROR;

			$headers = [];
		}

		return $this->send(
			compact('error', 'error_description', 'error_data'), $status_code, $headers
		);
	}
}
