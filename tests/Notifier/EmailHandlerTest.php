<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\Notifier\Facades\Notifier;
use Fuzz\ApiServer\Notifier\Handlers\Email\EmailHandler;
use Fuzz\ApiServer\Notifier\Handlers\Email\NotificationEmail;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\HttpException\BadRequestHttpException;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery;

class EmailHandlerTest extends AppTestCase
{
	public function testItReturnsFalseOnNotifyInfo()
	{
		$config = [
			'receivers' => [
				'devnull@fuzzproductions.com',
			],

			'notification_cooldown_min' => 15,

			'ignore_environments' => [
				'local',
				'testing',
			],

			'local_tz' => 'America/New_York',

			'cache_key_prefix' => 'fuzz:notifier',
		];

		$notifier = new EmailHandler($config);

		$this->assertFalse($notifier->notifyInfo('foo', 'production', ['bar' => 'baz']));
	}

	public function testItIgnoresEnvironmentsOnNotifyError()
	{
		$config = [
			'receivers' => [
				'devnull@fuzzproductions.com',
			],

			'notification_cooldown_min' => 15,

			'ignore_environments' => [
				'local',
				'fooEnvironment',
			],

			'local_tz' => 'America/New_York',

			'cache_key_prefix' => 'fuzz:notifier',
		];

		$notifier = new EmailHandler($config);

		$this->assertFalse($notifier->notifyError(new \Exception, 'fooEnvironment'));
	}

	public function testItSendsErrorEmail()
	{
		$this->app['config']->set('app.name', 'FooApp');
		$config = [
			'receivers' => [
				'devnull@fuzzproductions.com',
			],

			'notification_cooldown_min' => 15,

			'ignore_environments' => [
				'local',
				'fooEnvironment',
			],

			'local_tz' => 'America/New_York',

			'cache_key_prefix' => 'fuzz:notifier',
		];

		$exception = new \Exception('Foo Message');
		$notifier = new EmailHandler($config);

		$mailable = Mockery::mock(PendingMail::class);
		Mail::shouldReceive('to')->with(['devnull@fuzzproductions.com',])->once()->andReturn($mailable);
		$mailable->shouldReceive('queue')->with(Mockery::on(function (NotificationEmail $email) {
			$this->assertSame('FooApp Encountered an Error on production: Exception', $email->subject);

			return $email->environment === 'production';
		}))->once();

		$hashed = md5(get_class($exception) . ':' . $exception->getMessage());
		$key = "fuzz:notifier:$hashed";

		Cache::shouldReceive('get')->with($key)->once()->andReturn(null);
		Cache::shouldReceive('put')->with($key, true, 15);

		$this->assertTrue($notifier->notifyError($exception, 'production', EmailHandler::MINOR, ['foo' => 'bar']));
	}

	public function testItDoesNotSendErrorEmailIfInCooldown()
	{
		$this->app['config']->set('app.name', 'FooApp');
		$config = [
			'receivers' => [
				'devnull@fuzzproductions.com',
			],

			'notification_cooldown_min' => 15,

			'ignore_environments' => [
				'local',
				'fooEnvironment',
			],

			'local_tz' => 'America/New_York',

			'cache_key_prefix' => 'fuzz:notifier',
		];

		$exception = new \Exception('Foo Message');
		$notifier = new EmailHandler($config);

		$mailable = Mockery::mock(PendingMail::class);
		Mail::shouldReceive('to')->never();

		$hashed = md5(get_class($exception) . ':' . $exception->getMessage());
		$key = "fuzz:notifier:$hashed";

		Cache::shouldReceive('get')->with($key)->once()->andReturn(true);
		Cache::shouldReceive('put')->never();

		$this->assertFalse($notifier->notifyError($exception, 'production', EmailHandler::MINOR, ['foo' => 'bar']));
	}
}