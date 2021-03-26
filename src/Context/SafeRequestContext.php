<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Illuminate\Http\Request as LaravelRequest;
use Psr\Http\Message\RequestInterface;

class SafeRequestContext extends RequestContext
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    /**
     * @inheritdoc
     */
    protected function contextFromPSR(RequestInterface $request): array
    {
        return $this->makeSafe(parent::contextFromPSR($request));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromLaravel(LaravelRequest $request): array
    {
        return $this->makeSafe(parent::contextFromLaravel($request));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromString(string $request): array
    {
        return $this->makeSafe(parent::contextFromString($request));
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
