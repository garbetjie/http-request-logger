<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

class SafeRequestContext extends RequestContext
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    /**
     * Extract context from a PSR-compliant request.
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function contextFromPSR(RequestInterface $request): array
    {
        return $this->makeSafe(parent::contextFromPSR($request));
    }

    /**
     * Extract context from a Symfony request (which includes Laravel requests).
     *
     * @param Request $request
     * @return array
     */
    protected function contextFromSymfony(Request $request): array
    {
        return $this->makeSafe(parent::contextFromSymfony($request));
    }

    /**
     * @param array $context
     * @return array
     */
    protected function makeSafe(array $context): array
    {
        // Make headers safe.
        foreach ($this->headers as $header) {
            if (isset($context['headers'][$header])) {
                $context['headers'][$header] = $this->replacement;
            }
        }

        return $context;
    }
}
