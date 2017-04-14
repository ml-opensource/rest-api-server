<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Responder Configuration
	|--------------------------------------------------------------------------
	|
	| Maps formats to API Responders
	|
    */
	'responders' => [
		'json' => \Fuzz\ApiServer\Response\JsonResponder::class,
		'csv'  => \Fuzz\ApiServer\Response\CsvResponder::class,
	],
];