[back](index.md)

# Installation
1. Require the repository in your `composer.json`
1. Add the `ApiServerServiceProvider` to your application and publish its config `artisan vendor:publish --provider="Fuzz\ApiServer\Providers\ApiServerServiceProvider"`.
1. Extend the packaged exception handler for your app:

```php
    <?php

    namespace MyApp\Exceptions;

    use Fuzz\ApiServer\Exception\Handler as ExceptionHandler;

    class Handler extends ExceptionHandler
    {
        // ...
    }
```
