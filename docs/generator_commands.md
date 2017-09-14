[back](index.md)

# Generator Commands

The Fuzz API Server also comes with some nifty code generation commands.

## Setup

Add the service provider to the `providers` array in `config/app.php`

```
Fuzz\ApiServer\Providers\GeneratorConsoleCmdServiceProvider::class,
```

Alternatively, instead of adding the service provider in the `config/app.php` file, you can add the following code to your `app/Providers/AppServiceProvider.php` file, within the `register()` method:

```php
public function register()
{
    if ($this->app->environment() !== 'production') {
        $this->app->register(Fuzz\ApiServer\Providers\GeneratorConsoleCmdServiceProvider::class);
    }
    // ...
}
```

This will allow your application to load the generator commands on non-production environments.


## Usage

So how are these any different from the ones laravel comes with? They simply go the extra steps in generating sensible boilerplate.

What If I want to use laravel's generator commands? No worries, laravel's commands will stay put. As this library puts it's commands under `artisan gen:{command}`
 
So lets take a look at what we can do:
  
These generators really center around the model. Lets take a look.
```
artisan gen:model {name} 
	{--table= : The models db table. If left blank, it will use laravels table naming convetion. }`
	{--migration : Add a migration for the model. }
	{--controller : Add a controller with restful methods for the model. }
	{--seeder : Create a seeder for the model. }
	{--factory : Create a factory for the model. }
	{--tests : Generate tests for the model, and the controller if the --controller flag was passed. }
```

So you can see the `gen:model` command mimics laravel's `make:model` but really comes around full circle by letting you 
choose to generate all the pieces. Particularly the pieces that tend to have the same boilerplate.
 
And if you don't feel like typing all those arguments, simply run:

`artisan gen:for Book`

By default it will include every part. Want to exclude a part? 

`artisan gen:for Book --no-controller`

Want to generate a part that you previously excluded? Just  

`artisan gen:for Book`

Don't worry, it will only generate what doesnt't already exist, and will never overwrite anything.

The generated code also throws in best practices. For example, TDD comes out of the box. Wire up the generated 
controller methods to routes and see your tests start passing.