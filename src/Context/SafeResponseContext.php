<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    /**
     * Extract context from a PSR-compliant response.
     *
     * @param ResponseInterface $response
     * @return array
     */
    protected function contextFromPSR(ResponseInterface $response): array
    {
        return $this->makeSafe(parent::contextFromPSR($response));
    }

    /**
     * Extract context from a Symfony response (which includes Laravel responses).
     *
     * @param Response $response
     * @return array
     */
    protected function contextFromSymfony(Response $response): array
    {
        return $this->makeSafe(parent::contextFromSymfony($response));
    }

    /**
     * Extract context from the given response, using server variables.
     *
     * @param string $response
     * @return array
     */
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
