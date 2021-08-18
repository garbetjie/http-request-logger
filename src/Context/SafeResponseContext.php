<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\ResponseEntry;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

	/**
	 * @inheritdoc
	 */
    protected function contextFromPSR(ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($entry));
    }

	/**
	 * @inheritdoc
	 */
    protected function contextFromSymfony(ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromSymfony($entry));
    }

	/**
	 * @inheritdoc
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
