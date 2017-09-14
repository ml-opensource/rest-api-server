Laravel API Server
==================

A framework for rapid REST API development.

Read the [docs](docs/index.md).

### Installation
1. Require the repository in your `composer.json`
1. Add the `ApiServerServiceProvider` to your application and publish its config `artisan vendor:publish --provider="Fuzz\ApiServer\Providers\ApiServerServiceProvider"`.
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
Read the [docs](docs/index.md).
