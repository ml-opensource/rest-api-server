<?php

namespace Fuzz\ApiServer\Notifier\Handlers\Email;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
	use Queueable, SerializesModels;

	public $body;
	public $environment;
	public $at;

	/**
	 * Create a new message instance.
	 *
	 * @param string         $subject
	 * @param string         $body
	 * @param string         $environment
	 * @param \Carbon\Carbon $at
	 */
	public function __construct(string $subject, string $body, string $environment, Carbon $at)
	{
		$this->subject     = $subject;
		$this->body        = $body;
		$this->environment = $environment;
		$this->at          = $at;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		return $this->subject($this->subject)->view('email_notifier::notification')->with([
			'body' => $this->body,
		]);
	}
}
