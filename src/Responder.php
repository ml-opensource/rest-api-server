<?php

namespace Fuzz\ApiServer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Fuzz\ApiServer\Exception\HttpException;
use Illuminate\Contracts\Support\Arrayable;

class Responder
{
	/**
	 * Send a response.
	 *
	 * @param mixed $data
	 * @param int   $status_code
	 * @param array $headers
	 * @param array $context
	 * @return \Illuminate\Http\JsonResponse
	 */
	final public function send($data, $status_code, $headers, $context)
	{
		if ($data instanceof Arrayable) {
			$data = $data->toArray();
		}

		return Response::json(array_merge(compact('data'), $context), $status_code, $headers);
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
		if ($exception instanceof HttpException) {
			$error = $exception->getMessage();

			return $this->send(
				$exception->getData(),
				$exception::STATUS_CODE,
				$exception->getHeaders(),
				compact('error')
			);
		}

		/**
		 * Contextualize response with verbose information outside production.
		 *
		 * Report only "unknown" errors in production.
		 */
		if (Config::get('app.debug')) {
			$error = [
				'message' => $exception->getMessage(),
				'class' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			];
		} else {
			$error = 'E_UNKNOWN';
		}

		return $this->send(
			null,
			HttpException::STATUS_CODE,
			[],
			compact('error')
		);
	}
}
