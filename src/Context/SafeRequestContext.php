<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Garbetjie\Http\RequestLogging\RequestLogEntry;

class SafeRequestContext extends RequestContext
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    /**
     * @inheritdoc
     */
    protected function contextFromPSR(RequestLogEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromLaravel(RequestLogEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromLaravel($entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromString(RequestLogEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromString($entry));
    }

    /**
     * Replaces potentially sensitive information in the given context.
     *
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
