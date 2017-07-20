<?php

/**
 * @file
 * Defines the base API server.
 */

namespace Fuzz\ApiServer\Routing;

use Fuzz\ApiServer\Response\ResponseFactory;
use Fuzz\HttpException\AccessDeniedHttpException;
use Fuzz\HttpException\BadRequestHttpException;
use Fuzz\HttpException\ConflictHttpException;
use Fuzz\HttpException\NotFoundHttpException;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Routing\Controller as RoutingBaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Base Controller class.
 */
abstract class Controller extends RoutingBaseController
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
     * Default response format
     *
     * @var string
     */
    public $default_format = 'json';

    /**
     * @var \Fuzz\ApiServer\Response\ResponseFactory
     */
    protected $responder;

    /**
     * Returns the value of the pagination "per page" parameter.
     *
     * @param int $default
     *
     * @return int
     */
    public static function getPerPage($default = self::PAGINATION_PER_PAGE_DEFAULT): int
    {
        return min((int)Input::get(static::PAGINATION_PER_PAGE, $default), self::PAGINATION_PER_PAGE_MAXIMUM);
    }

    /**
     * API calls without a routed string will resolve to the base controller.
     * This method catches all of them and notifies the caller of failure.
     *
     * @param array $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function missingMethod($parameters = []): Response
    {
        // Check if there are valid methods that could have been used
        $url_parts = parse_url(Request::getRequestUri());
        $uri = $url_parts['path'];

        $valid_methods = [];

        foreach (Route::getRoutes() as $route) {
            if (// Ignore catch-all routes
                ! strpos($route->getActionName(), '@any')
                && // Ignore "method missing" routes
                ! strpos($route->getActionName(), '@missing')
                && // Catch only routes with URI regex strings catching the current request URI
                preg_match($route->bind(Request::instance())->getCompiled()->getRegex(), $uri)
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
     * Inform caller about available methods.
     *
     * @param array $valid_methods
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function expectMethods(array $valid_methods): Response
    {
        return $this->getResponder()->send([
            'error' => 'method_not_allowed',
            'error_data' => compact('valid_methods'),
        ], 405, [
            'Allow' => implode(', ', $valid_methods),
        ]);
    }

    /**
     * Produce a responder for sending responses.
     *
     * @return \Fuzz\ApiServer\Response\ResponseFactory
     */
    public function getResponder(): ResponseFactory
    {
        if (is_null($this->responder)) {
            $this->setResponder(app()->make(ResponseFactory::class));
        }

        return $this->responder;
    }

    /**
     * Set this controller's responder
     *
     * @param \Fuzz\ApiServer\Response\ResponseFactory $responder
     *
     * @return \Fuzz\ApiServer\Routing\Controller
     */
    public function setResponder(ResponseFactory $responder): Controller
    {
        $this->responder = $responder;

        return $this;
    }

    /**
     * Object not found.
     *
     * @param string $message
     * @param mixed $data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return void
     */
    protected function notFound($message = null, $data = null)
    {
        throw new NotFoundHttpException($message, $data);
    }

    /**
     * Created!
     *
     * @param mixed $data
     * @param array $headers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function created($data, $headers = []): Response
    {
        return $this->succeed($data, Response::HTTP_CREATED, $headers);
    }

    /**
     * Success!
     *
     * @param mixed $data
     * @param int $status_code
     * @param array $headers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function succeed($data, $status_code = Response::HTTP_OK, $headers = []): Response
    {
        // Append pagination data automatically
        if ($data instanceof AbstractPaginator) {
            $pagination = $this->getPagination($data);
            $data = $data->getCollection();

            $data = [
                'data' => $data,
                'pagination' => $pagination,
            ];
        }

        return $this->getResponder()
            ->setResponseFormat(Request::input('format', $this->default_format))
            ->makeResponse($data, $status_code, $headers);
    }

    /**
     * Get pagination metadata from a Paginator instance.
     *
     * @param  AbstractPaginator $paginator
     *
     * @return array
     * @todo this may be useless with the serializer
     */
    private function getPagination(AbstractPaginator $paginator)
    {
        // Pass in any additional query variables
        foreach (
            array_except(Request::instance()->query->all(), [
                self::PAGINATION_CURRENT_PAGE,
                self::PAGINATION_PER_PAGE,
            ]) as $key => $value
        ) {
            $paginator->addQuery($key, $value);
        }

        // Add our "per page" pagination parameter to the constructed URLs
        $paginator->addQuery(self::PAGINATION_PER_PAGE, $paginator->perPage());

        // Prepare useful pagination metadata
        return [
            'page' => $paginator->currentPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'next' => $paginator->nextPageUrl(),
            'previous' => $paginator->previousPageUrl(),
        ];
    }

    /**
     * Access denied.
     *
     * @param string $message
     * @param mixed $data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return void
     */
    protected function forbidden($message = null, $data = null)
    {
        throw new AccessDeniedHttpException($message, $data);
    }

    /**
     * Conflict
     *
     * @param string $message
     * @param string $data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     * @return void
     */
    protected function conflict($message = null, $data = null)
    {
        throw new ConflictHttpException($message, $data);
    }

    /**
     * Require a set of parameters.
     *
     * @return array
     * @todo reimplement as validation middleware
     */
    protected function requireParameters(): array
    {
        $passed_parameters = [];
        $missing_required = [];

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
     * Bad request.
     *
     * @param string $message
     * @param mixed $data
     */
    protected function badRequest($message = null, $data = null)
    {
        throw new BadRequestHttpException($message, $data);
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
     * @param $parameter_name
     *
     * @return array
     */
    protected function readArrayParameter($parameter_name)
    {
        return array_values(array_filter((array)Input::get($parameter_name)));
    }
}
