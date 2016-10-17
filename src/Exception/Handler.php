<?php

namespace Fuzz\ApiServer\Exception;

use Exception;
use Fuzz\ApiServer\Routing\Responder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Fuzz\HttpException\NotFoundHttpException;

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
		// Recast ModelNotFoundExceptions as local NotFoundHttpExceptions
		if ($e instanceof ModelNotFoundException) {
			$model_name = class_basename($e->getModel());
			$e = new NotFoundHttpException(
				"Unable to find $model_name."
			);
		}

		return (new Responder)->sendException($e);
	}
}
