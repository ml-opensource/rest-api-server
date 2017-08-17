<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Console\Command;

class PrintTokenCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'print:token {--length=40 : The length of the key (Default 40)}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'A generic token generator. Useful for generating api keys, secrets, oauth clients, etc...';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$length = $this->option('length');

		$this->info(bin2hex(random_bytes($length / 2)));
	}
}
