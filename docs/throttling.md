[back](index.md)

# Throttling
The Throttle module provides the ability to throttle your API based on arbitrary key sets. Its backed by and requires Redis. 

1. Add the service provider `\Fuzz\ApiServer\Throttling\Provider\ThrottleServiceProvider` to `config/app.php`.
1. Publish the config `php artisan vendor:publish --provider="Fuzz\ApiServer\Throttling\Provider"`

There are two ways to use throttles:

## 1. As Route Middleware
1. Add the route middleware in `app/Http/Kernel.php`:
    ```php
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        ...
        'ip-throttle'    => \Fuzz\ApiServer\Throttling\IPThrottler::class,
        'token-throttle' => \Fuzz\ApiServer\Throttling\TokenThrottler::class,
        ...
    ];
    ```
2. Apply it to your route:
    ```php
    $router->group(['middleware' => ['ip-throttle:60,1', 'magicbox:api',],], function (Router $router) {
        $router->get('/foo', 'FooController@bar');
    });
    ```

## 2. In Your Code
Examples:
```php
ArbitraryStringThrottler::assertThrottle($username, self::MAX_ATTEMPTS, self::DECAY_TIME_MINUTES);
```

```php
IPThrottler::assertThrottle($request->ip(), self::MAX_ATTEMPTS, self::DECAY_TIME_MINUTES);
```

```php
TokenThrottler::assertThrottle(Authorizer::getAccessToken()->id(), self::MAX_ATTEMPTS, self::DECAY_TIME_MINUTES);
```