<?php

namespace Fuzz\ApiServer\Exception;

use Exception;
use Fuzz\ApiServer\Routing\Responder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function render($request, Exception $e)
	{
		// Recast ModelNotFoundExceptions as local NotFoundExceptions
		if ($e instanceof ModelNotFoundException) {
			$e = new NotFoundHttpException(
				'Unable to find ' . class_basename($e->getModel()) . '.'
			);
		}

		return (new Responder)->sendException($e);
	}
}
