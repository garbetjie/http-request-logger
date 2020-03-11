<?php

namespace Garbetjie\Http\RequestLogging;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use function base64_encode;
use function get_class;
use function sprintf;
use function strlen;
use function substr;

class ResponseContextExtractor
{
    private $maxBodyLength;

    public function __construct(int $maxBodyLength = 16384)
    {
        $this->maxBodyLength = $maxBodyLength;
    }

    /**
     * @param ResponseInterface|Response $response
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function __invoke($response): array
    {
        switch (true) {
            case $response instanceof ResponseInterface:
                return $this->extractResponsePSR($response);

            case $response instanceof Response:
                return $this->extractResponseLaravel($response);

            case $response instanceof PromiseInterface:
                return $this->extractResponsePromise($response);

            default:
                throw new InvalidArgumentException(sprintf('Unknown response instance "%s" provided.', get_class($response)));
        }
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    private function extractResponsePSR($response)
    {
        $body = $response->getBody();
        $body->rewind();
        $contents = base64_encode($body->read($this->maxBodyLength));
        $body->rewind();

        return [
            'status_code' => $response->getStatusCode(),
            'body_length' => $body->getSize(),
            'body' => $contents,
            'headers' => normalize_headers($response->getHeaders()),
        ];
    }

    /**
     * @param PromiseInterface $promise
     * @return array
     */
    private function extractResponsePromise($promise)
    {
        return $this->extractResponsePSR(
            $promise->wait(true)
        );
    }

    /**
     * @param Response $response
     * @return array
     */
    private function extractResponseLaravel($response)
    {
        $body = $response->getContent();

        return [
            'status_code' => $response->getStatusCode(),
            'body_length' => strlen($body),
            'body' => base64_encode(substr($body, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($response->headers->all()),
        ];
    }
}
