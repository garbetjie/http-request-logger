<?php

namespace Garbetjie\Http\RequestLogging;

use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use function base64_encode;
use function get_class;
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
     * @param RequestInterface|Request $request
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function __invoke($request): array
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
     * @param RequestInterface $request
     * @return array
     */
    protected function extractRequestPSR(RequestInterface $request)
    {
        $body = $request->getBody();
        $body->rewind();
        $contents = base64_encode($body->read($this->maxBodyLength));
        $body->rewind();

        return [
            'method' => $request->getMethod(),
            'url' => (string)$request->getUri(),
            'body_length' => $body->getSize(),
            'body' => $contents,
            'headers' => normalize_headers($request->getHeaders()),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractRequestLaravel(Request $request)
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
