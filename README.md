Laravel API Server
==================

A RESTful framework for rapid API development.


### Installation
1. Register the custom Fuzz Composer repository: ```composer config repositories.fuzz composer https://satis.fuzzhq.com``` 
2. Register the composer package: ```composer require fuzz/api-server```

## Usage
### Basic usage

Register a base controller extending Fuzz\ApiServer\Controller and assign an API version:

    <?php
    
    class MyBaseController extends Fuzz\ApiServer\Controller
    {
    	const API_VERSION = 1.0;
    }

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

    <?php
    
    public function __construct()
    {
        parent::__construct();
        $raven_client = new Raven_Client(Config::get('raven.dsn'), Config::get('raven.options', []));
        $this->addLogHandler(new Monolog\Handler\RavenHandler($raven_client));
    }
    
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
    
    return $this->badRequest('E_INVALID_FIELDS', ['fields' => ['foo', 'bar']]);
    return $this->unauthorized('E_BAD_ACCESS_TOKEN');
    return $this->accessDenied('E_NICE_TRY');
    return $this->notFound('E_MISPLACED_MOJO');

Raise RESTful error exceptions outside of the controller context:

    <?php
    
    throw new Fuzz\ApiServer\Exception\BadRequestException(['fields' => ['foo', 'bar'], 'E_INVALID_FIELDS');
    throw new Fuzz\ApiServer\Exception\UnauthorizedException(null, 'E_BAD_ACCESS_TOKEN');
    throw new Fuzz\ApiServer\Exception\AccessDeniedException(null, 'E_NICE_TRY');
    throw new Fuzz\ApiServer\Exception\NotFoundException(null, 'E_MISPLACED_MOJO');
    
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