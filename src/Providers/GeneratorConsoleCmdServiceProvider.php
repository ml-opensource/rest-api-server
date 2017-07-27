<?php

namespace Fuzz\ApiServer\Providers;


use Fuzz\ApiServer\Console\GenControllerCommand;
use Fuzz\ApiServer\Console\GenFactoryCommand;
use Fuzz\ApiServer\Console\GenForCommand;
use Fuzz\ApiServer\Console\GenMigrateCommand;
use Fuzz\ApiServer\Console\GenModelCommand;
use Fuzz\ApiServer\Console\GenSeederCommand;
use Fuzz\ApiServer\Console\GenTestCommand;
use Fuzz\ApiServer\Console\PrintTokenCommand;
use Illuminate\Support\ServiceProvider;

class GeneratorConsoleCmdServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->app->runningInConsole()) {
			$this->commands([
				GenControllerCommand::class,
				GenFactoryCommand::class,
				GenForCommand::class,
				GenMigrateCommand::class,
				GenModelCommand::class,
				GenSeederCommand::class,
				GenTestCommand::class,
				PrintTokenCommand::class,
			]);
		}
	}
}