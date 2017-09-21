<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Action Logger Configuration
	|--------------------------------------------------------------------------
	|
	| Configure all options of the action logger
	|
	*/
	'enabled' => env('LOG_ACTIONS_ENABLED', true),

	'model_class' => \Fuzz\ApiServer\Logging\ActionLog::class,
];