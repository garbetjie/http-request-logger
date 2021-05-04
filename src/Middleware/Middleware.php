<?php

namespace Garbetjie\RequestLogging\Http\Middleware;

use Garbetjie\RequestLogging\Http\Logger;

abstract class Middleware
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
}
