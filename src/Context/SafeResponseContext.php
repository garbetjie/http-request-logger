<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    protected function extractResponsePSR(ResponseInterface $response)
    {
        return $this->makeSafe(parent::extractResponsePSR($response));
    }

    protected function extractResponseLaravel(Response $response)
    {
        return $this->makeSafe(parent::extractResponseLaravel($response));
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
