<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Garbetjie\Http\RequestLogging\RequestLogEntry;
use Illuminate\Http\Request as LaravelRequest;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use function base64_encode;
use function Garbetjie\Http\RequestLogging\normalize_headers;
use function get_class;
use function is_scalar;
use function is_string;
use function sprintf;
use function stripos;
use function strlen;
use function substr;

class RequestContext
{
    /**
     * @var int
     */
    protected $maxBodyLength;

    public function __construct(int $maxBodyLength = 16384)
    {
        $this->maxBodyLength = $maxBodyLength;
    }

    /**
     * @param RequestLogEntry $entry
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function __invoke(RequestLogEntry $entry): array
    {
        switch (true) {
            case $entry->request() instanceof LaravelRequest:
                return $this->contextFromLaravel($entry);

            case $entry->request() instanceof ServerRequestInterface:
            case $entry->request() instanceof RequestInterface:
                return $this->contextFromPSR($entry);

            case is_string($entry->request()):
                return $this->contextFromString($entry);

            default:
                throw new InvalidArgumentException(sprintf(
                    "Unknown request instance '%s' provided.",
                    is_scalar($entry->request()) ? $entry->request() : get_class($entry->request())
                ));
        }
    }

    /**
     * Extract context from the given request, using server variables.
     *
     * @param RequestLogEntry $entry
     * @return array
     */
    protected function contextFromString(RequestLogEntry $entry): array
    {
        $request = $entry->request();
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
            'id' => $entry->id(),
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
     * @param RequestLogEntry $entry
     * @return array
     */
    protected function contextFromPSR(RequestLogEntry $entry): array
    {
        $request = $entry->request();

        $body = $request->getBody();
        $body->rewind();
        $contents = base64_encode($body->read($this->maxBodyLength));
        $body->rewind();

        return [
            'id' => $entry->id(),
            'method' => $request->getMethod(),
            'url' => (string)$request->getUri(),
            'body_length' => $body->getSize(),
            'body' => $contents,
            'headers' => normalize_headers($request->getHeaders()),
        ];
    }

    /**
     * Extract context from a Laravel request.
     *
     * @param RequestLogEntry $entry
     * @return array
     */
    protected function contextFromLaravel(RequestLogEntry $entry): array
    {
        $request = $entry->request();
        $content = $request->getContent();

        return [
            'id' => $entry->id(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'body_length' => strlen($content),
            'body' => base64_encode(substr($content, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($request->headers->all()),
        ];
    }
}
