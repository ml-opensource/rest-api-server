<?php

/**
 * @file
 * Defines the base API server.
 */

namespace Fuzz\ApiServer;

use Fuzz\ApiServer\Exception\BadRequestException;
use Fuzz\ApiServer\Exception\ForbiddenException;
use Fuzz\ApiServer\Exception\NotFoundException;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

/**
 * API Base Controller class.
 */
abstract class Controller extends BaseController
{
	/**
	 * Parameter name for pagination controller: items per page.
	 *
	 * @var string
	 */
	const PAGINATION_PER_PAGE = 'per_page';

	/**
	 * Parameter name for pagination controller: current page.
	 *
	 * @var string
	 */
	const PAGINATION_CURRENT_PAGE = 'page';

	/**
	 * Default items per page.
	 *
	 * @var int
	 */
	const PAGINATION_PER_PAGE_DEFAULT = 10;

	/**
	 * Maximum items per page.
	 *
	 * @var int
	 */
	const PAGINATION_PER_PAGE_MAXIMUM = 50;

	/**
	 * Produce a responder for sending responses.
	 *
	 * @return Responder
	 */
	protected function getResponder()
	{
		return new Responder;
	}

	/**
	 * Success!
	 *
	 * @param mixed $data
	 * @param int   $status_code
	 * @param array $headers
	 * @param array $context
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function succeed($data, $status_code = 200, $headers = [])
	{
		// Append pagination data automatically
		if ($data instanceof AbstractPaginator) {
			$pagination = $this->getPagination($data);
			$data       = $data->getCollection();
		}

		return $this->getResponder()->send(compact('data', 'pagination'), $status_code, $headers);
	}

	/**
	 * Object not found.
	 *
	 * @param string $message
	 * @param mixed  $data
	 * @throws \Fuzz\ApiServer\Exception\NotFoundException
	 * @return void
	 */
	protected function notFound($message = null, $data = null)
	{
		throw new NotFoundException($message, $data);
	}

	/**
	 * Access denied.
	 *
	 * @param string $message
	 * @param mixed  $data
	 * @throws \Fuzz\ApiServer\Exception\ForbiddenException
	 * @return void
	 */
	protected function forbidden($message = null, $data = null)
	{
		throw new ForbiddenException($message, $data);
	}

	/**
	 * Bad request.
	 *
	 * @param string $message
	 * @throws \Fuzz\ApiServer\Exception\BadRequestException
	 * @return void
	 */
	protected function badRequest($message = null, $data = null)
	{
		throw new BadRequestException($message, $data);
	}

	/**
	 * Inform caller about available methods.
	 *
	 * @param array $valid_methods
	 * @return \Illuminate\Http\JsonResponse
	 */
	final private function expectMethods(array $valid_methods)
	{
		return $this->getResponder()->send(
			[
				'error' => 'method_not_allowed',
			], 405, [
				'Allow' => implode(', ', $valid_methods),
			]
		);
	}

	/**
	 * API calls without a routed string will resolve to the base controller.
	 * This method catches all of them and notifies the caller of failure.
	 *
	 * @param array $parameters
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function missingMethod($parameters = [])
	{
		// Check if there are valid methods that could have been used
		$url_parts = parse_url($_SERVER['REQUEST_URI']);
		$uri       = $url_parts['path'];

		$valid_methods = [];
		$request       = Request::instance();

		foreach (Route::getRoutes() as $route) {
			if (// Ignore catch-all routes
				! strpos($route->getActionName(), '@any')
				&& // Ignore "method missing" routes
				! strpos($route->getActionName(), '@missing')
				&& // Catch only routes with URI regex strings catching the current request URI
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
		$this->notFound();
	}

	/**
	 * Returns the value of the pagination "per page" parameter.
	 *
	 * @return int
	 */
	public static function getPerPage($default = self::PAGINATION_PER_PAGE_DEFAULT)
	{
		return min((int) Input::get(static::PAGINATION_PER_PAGE, $default), self::PAGINATION_PER_PAGE_MAXIMUM);
	}

	/**
	 * Get pagination metadata from a Paginator instance.
	 *
	 * @param  AbstractPaginator $paginator
	 * @return array
	 */
	final private function getPagination(AbstractPaginator $paginator)
	{
		// Pass in any additional query variables
		foreach (
			array_except(
				Request::instance()->query->all(), [
					self::PAGINATION_CURRENT_PAGE,
					self::PAGINATION_PER_PAGE
				]
			) as $key => $value
		) {
			$paginator->addQuery($key, $value);
		}

		// Add our "per page" pagination parameter to the constructed URLs
		$paginator->addQuery(self::PAGINATION_PER_PAGE, $paginator->perPage());

		// Prepare useful pagination metadata
		return [
			'page'     => $paginator->currentPage(),
			'total'    => $paginator->count(),
			'per_page' => $paginator->perPage(),
			'next'     => $paginator->nextPageUrl(),
			'previous' => $paginator->previousPageUrl(),
		];
	}

	/**
	 * Require a set of parameters.
	 *
	 * @return array
	 * @throws BadRequestException
	 * @todo reimplement as validation middleware
	 */
	protected function requireParameters()
	{
		$passed_parameters = [];
		$missing_required  = [];

		foreach (func_get_args() as $parameter_name) {
			if (! Input::has($parameter_name)) {
				$missing_required[] = $parameter_name;
			}

			$passed_parameters[] = Input::get($parameter_name);
		}

		if (count($missing_required) !== 0) {
			$this->badRequest('Required fields were not provided.', compact('missing_required'));
		}

		return $passed_parameters;
	}

	/**
	 * Suggest a set of parameters.
	 *
	 * @return array
	 * @todo reimplement as validation middleware
	 */
	protected function suggestParameters()
	{
		$passed_parameters = [];

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
		return array_values(array_filter((array) Input::get($parameter_name)));
	}
}
