<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Garbetjie\Http\RequestLogging\ResponseLogEntry;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    /**
     * Extract context from a PSR-compliant response.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromPSR(ResponseLogEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($entry));
    }

    /**
     * Extract context from a Laravel response.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromLaravel(ResponseLogEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromLaravel($entry));
    }

    /**
     * Extract context from the given response, using server variables.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromString(ResponseLogEntry $entry): array
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
