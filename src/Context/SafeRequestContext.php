<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\RequestEntry;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class SafeRequestContext extends RequestContext
{
    protected $headers = ['authorization', 'cookie'];
    protected $replacement = '***';

    /**
     * @inheritdoc
     */
    protected function contextFromPSR(RequestInterface $request, RequestEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($request, $entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromSymfony(SymfonyRequest $request, RequestEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromSymfony($request, $entry));
    }

    /**
     * @inheritdoc
     */
    protected function contextFromString(string $request, RequestEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromString($request, $entry));
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
