Laravel API Server
==================

A RESTful framework for rapid API development.


### Installation
1. Register the custom Fuzz Composer repository: ```composer config repositories.fuzz composer https://satis.fuzzhq.com``` 
1. Register the composer package: ```composer require fuzz/api-server```
1. Register the service provider in your AppServiceProvider:

    <?php
    
    public function register()
    {
        $this->app->register(new ApiServerServiceProvider($this->app));
    }

1. Extend the packaged route provider for your app:

    <?php
    
    use Fuzz\ApiServer\Providers\RouteServiceProvider as ServiceProvider;
    
    class RouteServiceProvider extends ServiceProvider
    {
        // ...
    }

1. Extend the packaged exception handler for your app:

    <?php
    
    namespace MyApp\Exceptions;
    
    use Fuzz\ApiServer\Exception\Handler as ExceptionHandler;
    
    class Handler extends ExceptionHandler
    {
        // ...
    }

1. Initialize the package dependencies after your database is configured: ```artisan api:initialize```

## Usage
### Basic usage

Register a base controller extending Fuzz\ApiServer\Routing\Controller:

    <?php
    
    class MyBaseController extends Fuzz\ApiServer\Routing\Controller {}

Register routes pointing to extensions of your base controller. Make a catch-all route to send all other requests through your base controller.

    <?php
    
    class MySpecificController extends MyBaseController
    {
        public function someEndpoint() {
            return $this->succeed('Foobar!');
        }
    }
    
    Route::get('some-endpoint', 'MySpecificController@someEndpoint');
    // ...
    Route::controller(null, 'MyBaseController');

You can add any Monolog-compatible handler for error logging:

Send mixed data:

    <?php
    
    return $this->succeed(['foo' => 'bar']);

Send any arrayable data:

    <?php
    
    return $this->succeed(Model::all());

Send any paginated data:

    <?php
    
    return $this->succeed(Model::paginate($this->getPerPage(Model::DEFAULT_PER_PAGE)));

Send RESTful errors with error codes and optional data:

    <?php
    
    $this->badRequest('That button does not do what you think it does.');
    $this->forbidden('Maybe next time.');
    $this->notFound();

Raise RESTful error exceptions outside of the controller context:

    <?php
    
    throw new Fuzz\ApiServer\Exception\BadRequestException;
    throw new Fuzz\ApiServer\Exception\ForbiddenException;
    throw new Fuzz\ApiServer\Exception\NotFoundException;
    
Require the user to provide certain parameters:

    <?php

    // Magically works with either JSON or form data
    list($foo, $bar) = $this->requireParameters('foo', 'bar');

Read a list of certain parameters:

    <?php
    
    list($foo, $bar) = $this->suggestParameters('foo', 'bar');

Special handling (with de-duplication) for reading arrays:

    <?php
    
    $stuff = $this->requireArrayParameter('stuff');

Handles nested JSON and form properties just fine:

    <?php
    
    // Corresponds with {"foo": {"bar": {"id": 9}}}
    list($foo, $bar_id) = $this->requireParameters('foo', 'foo.bar.id');
