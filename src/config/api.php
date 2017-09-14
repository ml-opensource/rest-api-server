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
		'xml'  => \Fuzz\ApiServer\Response\XMLResponder::class,
		'xls'  => \Fuzz\ApiServer\Response\XLSResponder::class,
		'xlsx' => \Fuzz\ApiServer\Response\XLSXResponder::class,
    ],
];