<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    protected function contextFromPSR(ResponseInterface $response): array
    {
        return $this->makeSafe(parent::contextFromPSR($response));
    }

    protected function contextFromSymfony(Response $response): array
    {
        return $this->makeSafe(parent::contextFromSymfony($response));
    }

    protected function contextFromString(string $response): array
    {
        return $this->makeSafe(parent::contextFromString($response));
    }

    /**
     * Ensures sensitive header values are replaced.
     *
     * @param array $context
     * @return array
     */
    protected function makeSafe(array $context): array
    {
        foreach ($this->headers as $header) {
            if (isset($context['headers'][$header])) {
                $context['headers'][$header] = $this->replacement;
            }
        }

        return $context;
    }
}
