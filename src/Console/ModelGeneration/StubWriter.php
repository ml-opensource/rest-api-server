<?php


namespace Fuzz\ApiServer\Console\Commands\ModelGeneration;

use Fuzz\ApiServer\Console\ModelGeneration\RelationStub;
use Illuminate\Filesystem\Filesystem;


/**
 * Handles writing the RelationStub to an actual file.
 *
 * @package Fuzz\ApiServer\Console\Commands\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class StubWriter
{
	/**
	 * @var RelationStub
	 */
	protected $stub;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * StubWriter constructor.
	 *
	 * @param RelationStub $stub
	 * @param Filesystem   $files
	 */
	public function __construct(RelationStub $stub, Filesystem $files)
	{
		$this->stub  = $stub;
		$this->files = $files;
	}

	/**
	 * Get the filesystem.
	 *
	 * @return Filesystem
	 */
	public function getFileSystem()
	{
		return $this->files;
	}

	/**
	 * Get the RelationStub
	 *
	 * @return RelationStub
	 */
	public function getStub()
	{
		return $this->stub;
	}

	/**
	 * Write changes to the file.
	 *
	 * @param string $path - The path to file.
	 *
	 * @return StubWriter
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function write(string $path): StubWriter
	{
		$file = $this->files->get($path);

		if ($this->shouldWriteTo($file)) {

			// Add in the use statement if it doesn't exist.
			if ($this->shouldWriteUseStatement($file)) {
				$file = $this->writeUseStatement($file);
			}

			// Add the relationship.
			$file = $this->writeRelation($file);

			// Write the changes to disk now.
			$this->files->put($path, $file);

		}

		return $this;
	}


	public function writeRelation(string $file): string
	{
		return preg_replace(
			'/\{(.*)\}/s', // This selects everything starting from the open bracket in the class till the last close bracket in the file.
			'{$1' . "\n" . (string) $this->stub . '}',
			$file,
			1
		);
	}

	/**
	 * Adds the use statement under the first use statement it finds.
	 *
	 * Generally this shouldn't cause issues. But it certainly doesnt cover everything.
	 *
	 * @param string $file - The string to write the change.
	 *
	 * @return mixed
	 */
	public function writeUseStatement(string $file)
	{
		return preg_replace('/(use .*;)/', '$1 ' . "\n{$this->stub->getUseStatement()}", $file, 1);
	}

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public function shouldWriteTo(string $file): bool
	{
		return ! strpos($file, $this->stub->getFunctionDeclaration());
	}

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public function shouldWriteUseStatement(string $file): bool
	{
		return ! strpos($file, $this->stub->getUseStatement());
	}
}
