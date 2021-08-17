<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\ResponseEntry;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SafeResponseContext extends ResponseContext
{
    protected $headers = ['set-cookie'];
    protected $replacement = '***';

    /**
     * @inheritdoc
	 */
    protected function contextFromPSR(ResponseInterface $response, ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromPSR($response, $entry));
    }

    /**
	 * @inheritdoc
     */
    protected function contextFromSymfony(SymfonyResponse $response, ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromSymfony($response, $entry));
    }

    /**
     * @inheritdoc
	 */
    protected function contextFromString(string $response, ResponseEntry $entry): array
    {
        return $this->makeSafe(parent::contextFromString($response, $entry));
    }

    /**
     * @inheritdoc
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
