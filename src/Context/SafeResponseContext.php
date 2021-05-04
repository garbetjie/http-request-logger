<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\ResponseEntry;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    /**
     * Extract context from a PSR-compliant response.
     *
     * @param ResponseEntry $entry
     * @return array
     */
    protected function contextFromPSR(ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($entry));
    }

    /**
     * Extract context from a Laravel response.
     *
     * @param ResponseEntry $entry
     * @return array
     */
    protected function contextFromSymfony(ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromSymfony($entry));
    }

    /**
     * Extract context from the given response, using server variables.
     *
     * @param ResponseEntry $entry
     * @return array
     */
    protected function contextFromString(ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromString($entry));
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
