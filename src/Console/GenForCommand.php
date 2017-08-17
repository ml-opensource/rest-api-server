<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenForCommand extends Command
{
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'gen:for {model : The model to generate everything for.}
		{--table= : Define the database table for the model.}
		{--no-migration : Do not generate a migration class.}
		{--no-controller : Do not generate a controller class.}
		{--no-seeder : Do not generate a seeder class.}
		{--no-factory : Do not generate a factory class.}
		{--no-tests : Do not generate test classes.}
		{--no-dumpautoload : Do not make composer dumpautoload.}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create the entire kitchen sink for an existing model or brand new model.';

	public function handle()
	{
		$options = [
			'name'           => $this->getModelInput(),
			'--table'        => $this->getTableInput(),
			'--migration'    => (! $this->option('no-migration')),
			'--controller'   => (! $this->option('no-controller')),
			'--seeder'       => (! $this->option('no-seeder')),
			'--factory'      => (! $this->option('no-factory')),
			'--tests'        => (! $this->option('no-tests')),
			'--dumpautoload' => (! $this->option('no-dumpautoload')),
		];

		$this->call('gen:model', $options);
	}

	/**
	 * Get the table input or derive it from the model input.
	 *
	 * @return string
	 */
	public function getTableInput(): string
	{
		return $this->option('table') ?? Str::plural(Str::snake(class_basename($this->getModelInput())));
	}

	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getModelInput()
	{
		return trim($this->argument('model'));
	}
}
