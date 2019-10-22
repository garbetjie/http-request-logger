<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use function base64_encode;
use function bin2hex;
use function get_class;
use function random_bytes;
use function strlen;
use function substr;

class RequestContextExtractor
{
    private $maxBodyLength;

    public function __construct(int $maxBodyLength = 16384)
    {
        $this->maxBodyLength = $maxBodyLength;
    }

    /**
     * @param RequestInterface|ServerRequestInterface|Request $request
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function extract($request)
    {
        switch (true) {
            case $request instanceof Request:
                return $this->extractRequestLaravel($request);

            case $request instanceof ServerRequestInterface:
            case $request instanceof RequestInterface:
                return $this->extractRequestPSR($request);

            default:
                throw new InvalidArgumentException(sprintf('Unknown request instance "%s" provided.', get_class($request)));
        }
    }

    /**
     * @param RequestInterface|ServerRequestInterface $request
     * @return array
     */
    private function extractRequestPSR($request)
    {
        $body = $request->getBody();
        $body->rewind();

        return [
            'method' => $request->getMethod(),
            'url' => (string)$request->getUri(),
            'body_length' => $body->getSize(),
            'body' => $body->read($this->maxBodyLength),
            'headers' => normalize_headers($request->getHeaders()),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    private function extractRequestLaravel($request)
    {
        $content = $request->getContent();

        return [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'body_length' => strlen($content),
            'body' => base64_encode(substr($content, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($request->headers->all()),
        ];
    }
}