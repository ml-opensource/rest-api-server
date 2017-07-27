<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class GenSeederCommand extends SeederMakeCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'gen:seeder';

	protected function baseFire()
	{
		$name = $this->qualifyClass($this->getNameInput());

		$path = $this->getPath($name);

		// First we will check to see if the class already exists. If it does, we don't want
		// to create the class and overwrite the user's code. So, we will bail out so the
		// code is untouched. Otherwise, we will continue generating this class' files.
		if ($this->alreadyExists($this->getNameInput())) {
			$this->error($this->type . ' already exists!');

			return false;
		}

		// Next, we will generate the path to the location where this class' file should get
		// written. Then, we will build the class and make the proper replacements on the
		// stub files so that it gets the correctly formatted namespace and class name.
		$this->makeDirectory($path);

		$this->files->put($path, $this->buildClass($name));

		$this->info($this->type . ' created successfully.');
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->baseFire();

		if ($this->option('dumpautoload')) {
			$this->composer->dumpAutoloads();
		}
	}

	/**
	 * Build the class with the given name.
	 *
	 * Remove the base controller import if we are already in base namespace.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function buildClass($name)
	{
		$replace = [];

		$replace = $this->buildModelReplacements($replace);

		return str_replace(
			array_keys($replace), array_values($replace), parent::buildClass($name)
		);
	}

	/**
	 * Build the model replacement values.
	 *
	 * @param  array $replace
	 *
	 * @return array
	 */
	protected function buildModelReplacements(array $replace)
	{
		$modelClass = $this->parseClass($this->getModelInput());

		if (! class_exists($modelClass)) {
			if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
				$this->call('gen:model', ['name' => $modelClass]);
			}
		}

		return array_merge($replace, [
			'DummyFullModelClass' => $modelClass,
			'DummyModelClass'     => class_basename($modelClass),
		]);
	}

	/**
	 * Get the fully-qualified class name.
	 *
	 * @param string      $class
	 * @param string|null $namespace
	 *
	 * @return string
	 */
	protected function parseClass(string $class = null, string $namespace = null): string
	{
		if (preg_match('([^A-Za-z0-9_/\\\\])', $class)) {
			throw new \InvalidArgumentException('Class name contains invalid characters.');
		}

		$class         = trim(str_replace('/', '\\', $class), '\\');
		$rootNamespace = $namespace ?? $this->laravel->getNamespace();

		if (! Str::startsWith($class, $rootNamespace)) {
			$class = $rootNamespace . $class;
		}

		return $class;
	}

	/**
	 * Get the model to assist with Seeder generation.
	 *
	 * @return string
	 */
	protected function getModelInput()
	{
		return trim($this->option('model')) ?: Str::studly(Str::singular(str_ireplace('Seeder', '', $this->getNameInput())));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['model', 'm', InputOption::VALUE_OPTIONAL, 'The model associated with the seeder.'],
			['dumpautoload', 'da', InputOption::VALUE_REQUIRED, 'Specify if the composer should dump its autoloader.',
				true,
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__ . '/stubs/seeder.stub';
	}
}
