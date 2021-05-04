<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\RequestEntry;

class SafeRequestContext extends RequestContext
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    /**
     * @inheritdoc
     */
    protected function contextFromPSR(RequestEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromSymfony(RequestEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromSymfony($entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromString(RequestEntry $entry): array
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
