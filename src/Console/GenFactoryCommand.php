<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class GenFactoryCommand extends GeneratorCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'gen:factory';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Factory file.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Factory';

	/**
	 * The Composer instance.
	 *
	 * @var \Illuminate\Support\Composer
	 */
	protected $composer;

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
		$modelClass = $this->parseClass($this->getNameInput());

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
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__ . '/stubs/factory.stub';
	}

	/**
	 * Get the destination class path.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel->databasePath() . '/factories/' . $name . 'Factory.php';
	}

	/**
	 * Parse the class name and format according to the root namespace.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function qualifyClass($name)
	{
		return $name;
	}
}
