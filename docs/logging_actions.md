[Back](index.md)

# Logging Actions
The ActionLogging module provides the ability to write all actions to a store (currently MySQL). To enable logging:

1. Pull in the `\Fuzz\ApiServer\Logging\Provider\ActionLoggerServiceProvider::class` into your services providers in `config/app.php`.
1. Publish the action log config and migrations with `php artisan vendor:publish --provider="\Fuzz\ApiServer\Logging\Provider\ActionLoggerServiceProvider"`.
1. Modify the config and migrations as needed.
1. Extend `\Fuzz\ApiServer\Logging\Middleware\ActionLoggerMiddleware` into your project, implement abstract methods, and add it to the middleware stack in `app/Http/Kernel.php`
    ```php
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        ...
        \Your\App\Namespace\To\Middleware::class,
        ...
    ];
    ```
1. Run the migration and log.

Optionally, you can include the `\Fuzz\ApiServer\Logging\Traits\LogsModelEvents` trait in your models to log all model events.