<?php

/**
 * @file
 * Defines the base API server.
 */

namespace Fuzz\ApiServer;

use Monolog\Logger;
use Illuminate\Support\Facades\App;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * API Base Controller class.
 */
class Controller extends BaseController
{
	/**
	 * Parameter name for pagination controller: items per page.
	 * @var string
	 */
	const PAGINATION_PER_PAGE = 'per_page';

	/**
	 * Default items per page.
	 * @var int
	 */
	const PAGINATION_PER_PAGE_DEFAULT = 10;

	/**
	 * The API version string for an implementation of this controller.
	 * @var string
	 */
	const API_VERSION = false;

	/**
	 * Default cache time for cachable responses.
	 * @var int
	 */
	const CACHE_TIME = 600;

	/**
	 * Logger instance.
	 * @var Monolog\Logger
	 */
	private $logger;

	/**
	 * Class constructor.
	 * Register exception handlers for handling all exceptions within the app RESTfully.
	 *
	 * @return void
	 */
	public function __construct()
	{
		// Catch all errors and notify the caller RESTfully
		App::error(function (\Exception $exception) {
			return $this->fail($exception);
		});

		$this->logger = new Logger('API');
	}

	/**
	 * Success!
	 *
	 * @param mixed $data
	 * @param int   $status_code
	 * @param array $extra
	 * @param array $extra_headers
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function succeed($data, $status_code = 200, $headers = array(), $context = array())
	{
		// Handle paginated data differently
		if ($data instanceof Paginator) {
			// Add our per_page pagination parameter to the constructed URLs
			$data->addQuery(static::PAGINATION_PER_PAGE, $this->getPerPage());

			$current_page = $data->getCurrentPage();
			$last_page    = (int) $data->getLastPage();

			// Prepare useful pagination metadata
			$pagination = array(
				'page'     => $current_page,
				'total'    => $data->getTotal(),
				'per_page' => $data->getPerPage(),
				'next'     => $current_page < $last_page ? $data->getUrl($current_page + 1) : null,
				'previous' => $current_page > 1 ? $data->getUrl($current_page - 1) : null,
			);

			return $this->respond(
				$data->getCollection()->toArray(),
				$status_code,
				$headers,
				array_merge($context, compact('pagination'))
			);
		} elseif ($data instanceof Arrayable) {
			return $this->respond(
				$data->toArray(),
				$status_code,
				$headers,
				$context
			);
		}

		return $this->respond(
			$data,
			$status_code,
			$headers,
			$context
		);
	}

	/**
	 * Notify the caller of failure.
	 *
	 * @param \Exception $exception
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	final private function fail(\Exception $exception)
	{
		/**
		 * Handle known HTTP exceptions RESTfully.
		 */
		if ($exception instanceof HttpException) {
			$error = ($exception->getMessage() ?: $this->getGenericError($exception));

			$this->logger->addWarning($error, compact('exception'));

			return $this->respond(
				null,
				$exception->getStatusCode(),
				$exception->getHeaders(),
				compact('error')
			);
		}

		/**
		 * Handle all other errors generically.
		 */

		/**
		 * Log an emergency.
		 */
		$this->logger->addEmergency($exception->getMessage(), compact('exception'));

		/**
		 * Contextualize response with verbose information outside production.
		 *
		 * Report only "unknown" errors in production.
		 */
		return $this->respond(
			Config::get('app.debug') ? $exception->getMessage() : 'E_UNKNOWN',
			SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR
		);
	}

	/**
	 * Object not found.
	 *
	 * @param mixed $data
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function notFound($error)
	{
		return $this->respond(null, 404, [], compact('error'));
	}

	/**
	 * Access denied.
	 *
	 * @param mixed $data
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function accessDenied($error)
	{
		return $this->respond(null, 403, [], compact('error'));
	}

	/**
	 * Unauthorized.
	 *
	 * @param mixed $data
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function unauthorized($error)
	{
		return $this->respond(null, 401, [], compact('error'));
	}

	/**
	 * Bad request.
	 *
	 * @param mixed $data
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function badRequest($error)
	{
		return $this->respond(null, 400, [], compact('error'));
	}

	/**
	 * Inform caller about available methods.
	 *
	 * @param array $valid_methods
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function expectMethods(array $valid_methods)
	{
		throw new MethodNotAllowedHttpException($valid_methods);
	}

	/**
	 * Return a JSON response to the caller.
	 *
	 * @param mixed $data
	 * @param int   $status_code
	 * @param array $headers
	 * @param array $context
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	final private function respond($data, $status_code, $headers = array(), $context = array())
	{
		return Response::json(
			array_merge(compact('data'), $context),
			$status_code,
			array_merge(
				array(
					'Cache-Control' =>
						($status_code === 200 && Request::method() === 'GET')
						? 'public, max-age=' .  static::CACHE_TIME
						: 'private, max-age=0'
				),
				$headers
			)
		);
	}

	/**
	 * Retrieve the logger.
	 * @return Monolog\Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * API calls without a routed string will resolve to the base controller.
	 * This method catches all of them and notifies the caller of failure.
	 *
	 * @param array $parameters
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function missingMethod($parameters = array())
	{
		if (! static::API_VERSION) {
			return $this->badRequest('E_INVALID_API_VERSION');
		}

		// Check if there are valid methods that could have been used
		$url_parts = parse_url($_SERVER['REQUEST_URI']);
		$uri       = $url_parts['path'];

		$valid_methods = array();
		$request = Request::instance();

		foreach (Route::getRoutes() as $route) {
			if (
				// Ignore catch-all routes
				! strpos($route->getActionName(), '@any') &&
				// Ignore "method missing" routes
				! strpos($route->getActionName(), '@missing') &&
				// Catch only routes with URI regex strings catching the current request URI
				preg_match($route->bind($request)->getCompiled()->getRegex(), $uri)
			) {
				$valid_methods = array_merge($valid_methods, array_map('strtoupper', $route->methods()));
			}
		}

		// If there are valid methods available, let the client know
		if (count($valid_methods) !== 0) {
			return $this->expectMethods($valid_methods);
		}

		// Otherwise, this is a simple 404
		return $this->notFound('E_NO_ROUTE');
	}

	/**
	 * Resolve an exception to a generic error.
	 */
	final private function getGenericError(\Exception $exception)
	{
		switch (get_class($exception)) {
			case 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException':
				return 'E_NOT_FOUND';
				break;
			case 'Symfony\Component\HttpKernel\Exception\BadRequestHttpException':
				return 'E_BAD_REQUEST';
				break;
			case 'Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException':
				return 'E_ACCESS_DENIED';
				break;
			case 'Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException':
				return 'E_UNAUTHORIZED';
				break;
			case 'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException':
				return 'E_METHOD_NOT_ALLOWED';
				break;
		}

		return 'E_UNKNOWN';
	}

	/**
	 * Returns the value of the pagination "per page" parameter.
	 *
	 * @return int
	 */
	protected function getPerPage()
	{
		return (int) Input::get(static::PAGINATION_PER_PAGE, static::PAGINATION_PER_PAGE_DEFAULT);
	}

	/**
	 * Require a set of parameters.
	 *
	 * @return array
	 * @throws BadRequestException
	 */
	protected function requireParameters()
	{
		$passed_parameters = array();
		$missing_required  = array();

		foreach (func_get_args() as $parameter_name) {
			if (! Input::has($parameter_name)) {
				$missing_required[] = $parameter_name;
			}

			$passed_parameters[] = Input::get($parameter_name);
		}

		if (count($missing_required) !== 0) {
			throw new BadRequestException(
				sprintf('Missing required parmeters: %s.', implode(', ', $missing_required))
			);
		}

		return $passed_parameters;
	}

	/**
	 * Suggest a set of parameters.
	 *
	 * @return array
	 */
	protected function suggestParameters()
	{
		$passed_parameters = array();

		foreach (func_get_args() as $parameter_name) {
			$passed_parameters[] = Input::get($parameter_name, null);
		}

		return $passed_parameters;
	}

	/**
	 * Read an array parameter.
	 *
	 * @return array
	 */
	protected function readArrayParameter($parameter_name)
	{
		if (Input::isJson()) {
			return array_filter(array_unique((array) Input::json($parameter_name)));
		}

		return array_filter(array_unique((array) Input::get($parameter_name)));
	}
}
