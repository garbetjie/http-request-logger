<?php

namespace Garbetjie\Http\RequestLogging\Context;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use function base64_encode;
use function Garbetjie\Http\RequestLogging\normalize_headers;
use function get_class;
use function is_string;
use function sprintf;
use function stripos;
use function strlen;
use function substr;

class RequestContext
{
    private $maxBodyLength;

    public function __construct(int $maxBodyLength = 16384)
    {
        $this->maxBodyLength = $maxBodyLength;
    }

    /**
     * @param RequestInterface|Request|string $request
     * @return array
     *@throws InvalidArgumentException
     *
     */
    public function __invoke($request): array
    {
        switch (true) {
            case $request instanceof Request:
                return $this->contextFromSymfony($request);

            case $request instanceof ServerRequestInterface:
            case $request instanceof RequestInterface:
                return $this->contextFromPSR($request);

            case is_string($request):
                return $this->contextFromString($request);

            default:
                throw new InvalidArgumentException(sprintf('Unknown request instance "%s" provided.', get_class($request)));
        }
    }

    /**
     * Extract context from the given request, using server variables.
     *
     * @param string $request
     * @return array
     */
    protected function contextFromString(string $request): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'http_') === 0) {
                $headers[substr($key, 5)] = $value;
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $url = null;

        // If we have environment values indicating we're processing an HTTP request, then we can attempt to extract them.
        if (isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
            $url = sprintf('%s://%s%s',
                !empty($_SERVER['HTTPS']) ? 'https' : 'http',
                $_SERVER['HTTP_HOST'],
                $_SERVER['REQUEST_URI']
            );
        }

        return [
            'method' => $method,
            'url' => $url,
            'body_length' => strlen($request),
            'body' => base64_encode(substr($request, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($headers),
        ];
    }

    /**
     * Extract context from a PSR-compliant request.
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function contextFromPSR(RequestInterface $request): array
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
     * Extract context from a Symfony request (which includes Laravel requests).
     *
     * @param Request $request
     * @return array
     */
    protected function contextFromSymfony(Request $request): array
    {
        $content = $request->getContent();

        return [
            'method' => $request->getMethod(),
            'url' => $request->getUri(),
            'body_length' => strlen($content),
            'body' => base64_encode(substr($content, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($request->headers->all()),
        ];
    }
}
