<?php

namespace Fuzz\ApiServer\Response;


class XLSXResponder extends XLSResponder implements Responder
{
    /**
     * Content type for this response
     *
     * @const string
     */
    const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8';

    /**
     * File format.
     *
     * @var string
     */
    protected $format = 'xlsx';
}
