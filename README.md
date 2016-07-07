Laravel API Server
==================

A framework for rapid REST API development.

### Installation
1. Require the repository in your `composer.json`
1. Extend the packaged route provider for your app:

```
    <?php
    
    namespace MyApp\Providers;
    
    use Fuzz\ApiServer\Providers\RouteServiceProvider as ServiceProvider;
    
    class RouteServiceProvider extends ServiceProvider
    {
        // ...
    }
```
1. Extend the packaged exception handler for your app:

```
    <?php
    
    namespace MyApp\Exceptions;
    
    use Fuzz\ApiServer\Exception\Handler as ExceptionHandler;
    
    class Handler extends ExceptionHandler
    {
        // ...
    }
```

## Usage
### Basic usage

Register a base controller extending Fuzz\ApiServer\Routing\Controller:

```
    <?php
    
    class MyBaseController extends Fuzz\ApiServer\Routing\Controller {}
```
Register routes pointing to extensions of your base controller. Make a catch-all route to send all other requests through your base controller.

```
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
```
### ResourceControllers
Resource controllers extend functionality of `fuzz/magic-box` repositories and provide CRUD and authorization functionality out of the box.

Your application should extend the base `fuzz/api-server` Resource controller:

```
<?php

namespace MyApp\Http\Controllers;

use Fuzz\ApiServer\Routing\ResourceController as BaseResourceController;

class ResourceController extends BaseResourceController
{
	// ...
}

```

And to define a route for a resource in your `routes.php`: `$router->restful('User');`. The `restful` route macro is defined in `Fuzz\ApiServer\Providers\RouteServiceProvider`.


If any resources need to override the default functionality, you can create a specific ResourceController by extending your application's base ResourceController:

`app/Http/Controllers/Resources/Users.php`:

```
<?php

namespace MyApp\Http\Controllers\Resources;

use Illuminate\Http\Request;
use Fuzz\MagicBox\Contracts\Repository;
use MyApp\Http\Controllers\ResourceController;

class Users extends ResourceController
{
	public function index(Repository $repository, Request $request)
	{
		// custom index...
	{
}

```

You can then point your restful route to your custom ResourceController:
 in `routes.php`: `$router->restful('Run', 'Resources\Users');`

### Returning that sweet, sweet, data
Send mixed data:

```
    <?php
    
    return $this->succeed(['foo' => 'bar']);
```
Send any arrayable data:

```
    <?php
    
    return $this->succeed(Model::all());
```
Send any paginated data:

```
    <?php
    
    return $this->succeed(Model::paginate($this->getPerPage(Model::DEFAULT_PER_PAGE)));
```
Send RESTful errors with error codes and optional data:

```
    <?php
    
    $this->badRequest('That button does not do what you think it does.');
    $this->forbidden('Maybe next time.');
    $this->notFound();
```
Raise RESTful error exceptions outside of the controller context:

```
    <?php
    
	throw new Symfony\Component\HttpKernel\Exception\ConflictHttpException;
	throw new Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	throw new Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
	throw new Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
```
Require the user to provide certain parameters:

```
    <?php

    // Magically works with either JSON or form data
    list($foo, $bar) = $this->requireParameters('foo', 'bar');
```
Read a list of certain parameters:

```
    <?php
    
    list($foo, $bar) = $this->suggestParameters('foo', 'bar');
```
Special handling (with de-duplication) for reading arrays:

```
    <?php
    
    $stuff = $this->requireArrayParameter('stuff');
```
Handles nested JSON and form properties just fine:

```
    <?php
    
    // Corresponds with {"foo": {"bar": {"id": 9}}}
    list($foo, $bar_id) = $this->requireParameters('foo', 'foo.bar.id');
```

### CORS Middleware
Configuring the CORS middleware is as simple as adding `Fuzz\ApiServer\Routing\CorsMiddleware` to the `$middleware` array in `app/Http/Kernel.php`.

## Serializing Data
`fuzz/api-server` wraps [league/fractal](http://fractal.thephpleague.com/) to serialize data output. `Fuzz\ApiServer\Routing\ResourceController` uses the `Fuzz\ApiServer\Utility\SerializesData` trait so any ResourceController extending it will do so as well. You can use serialization in any controller by including the trait and using it's methods.

1. `Fuzz\ApiServer\Utility\SerializesData` exposes `serialize` for single items and `serializeCollection` for collections. They will both accept an `Illuminate\Pagination\AbstractPaginator` or a simple array for data, a transformer as a string or `\Closure`, and a format (defaults to json).
1. Controllers extending `Fuzz\ApiServer\Routing\ResourceController` can use `Fuzz\ApiServer\Routing\ResourceController@modelTransformer` to resolve a model class' transformer. A transformer is defined in a public model property `model_transformer` and can be of any type accepted by `serialize` and `serializeCollection`

`fuzz/api-server` provides some basic transformers out of the box:

```
use Fuzz\Data\Serialization\FuzzModelTransformer; // array -> json
use Fuzz\Data\Serialization\FuzzExportableModelTransformer; // array -> csv
```

And some serializers:

```
use Fuzz\Data\Serialization\FuzzDataArraySerializer; // to array (and eventually to json)
use Fuzz\Data\Serialization\FuzzCsvDataArraySerializer; // to csv
```

