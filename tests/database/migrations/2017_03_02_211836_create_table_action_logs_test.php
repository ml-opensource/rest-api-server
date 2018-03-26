<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableActionLogsTest extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('action_logs', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('user_id')->unsigned()->nullable();
			$table->string('client_id')->nullable();
			$table->string('resource')->nullable();
			$table->string('resource_id')->nullable();
			$table->string('action');
			$table->string('request_id')->nullable();
			$table->string('error_status')->nullable();
			$table->text('note')->nullable();
			$table->string('ip');
			$table->json('meta')->nullable();
			$table->timestamp('created_at')->nullable();

			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('client_id')->references('id')->on('oauth_clients');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('action_logs');
	}
}
