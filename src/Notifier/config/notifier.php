<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Notification Handler Stack
	|--------------------------------------------------------------------------
	|
	| When a notification is triggered, all handlers in the stack will receive
	| it.
	|
	*/

	'handler_stack' => [
		\Fuzz\ApiServer\Notifier\Handlers\Email\EmailHandler::class => [
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
		],

		\Fuzz\ApiServer\Notifier\Handlers\ActionLog\ActionLogHandler::class => [],

		\Fuzz\ApiServer\Notifier\Handlers\Jira\JiraHandler::class => [
			'jira' => [
				'jiraHost'     => env('JIRA_HOST'),
				'jiraUser'     => env('JIRA_REPORTER'),
				'jiraPassword' => env('JIRA_REPORTER_PW'),
			],

			'ignore_environments' => [
				'local',
				'testing',
			],

			'project_key' => 'FPROJ',

			'errors' => [
				'priority' => 'Critical',

				'issue_type' => 'Bug',

				'labels' => [
					'[REQ-REVIEW]',
					'[AUTO-GENERATED]',
					'[API]',
				],
			],

			'search_statuses' => [
				'To Do',
				'In Progress',
				'In Review',
				'Blocked',
			],

			'versions' => [
				'Foo Version',
			],

			'components' => [
				'API'
			],

			'local_tz' => 'America/New_York',
		],
	],
];