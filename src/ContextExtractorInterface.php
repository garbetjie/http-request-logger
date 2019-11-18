<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ContextExtractorInterface
{
    /**
     * Extracts the context from the given object. The object can be either a request or a response.
     *
     * @param RequestInterface|ResponseInterface|Request|Response $from
     * @return array
     */
    public function extract($from): array;
}
