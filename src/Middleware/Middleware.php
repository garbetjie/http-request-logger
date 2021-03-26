<?php

namespace Garbetjie\Http\RequestLogging\Middleware;

use Garbetjie\Http\RequestLogging\Logger;

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
