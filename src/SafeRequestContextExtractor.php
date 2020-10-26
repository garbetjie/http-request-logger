<?php

namespace Garbetjie\Http\RequestLogging;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

class SafeRequestContextExtractor extends RequestContextExtractor
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    protected function extractRequestPSR(RequestInterface $request)
    {
        return $this->makeSafe(parent::extractRequestPSR($request));
    }

    protected function extractRequestLaravel(Request $request)
    {
        return $this->makeSafe(parent::extractRequestLaravel($request));
    }

    protected function makeSafe(array $context)
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
