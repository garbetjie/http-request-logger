<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Response;
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
     * @return array
     * @throws InvalidArgumentException
     */
    public function extract($response)
    {
        switch (true) {
            case $response instanceof ResponseInterface:
                return $this->extractResponsePSR($response);

            case $response instanceof Response:
                return $this->extractResponseLaravel($response);

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

        return [
            'status_code' => $response->getStatusCode(),
            'body_length' => $body->getSize(),
            'body' => base64_encode($body->read($this->maxBodyLength)),
            'headers' => normalize_headers($response->getHeaders()),
        ];
    }

    /**
     * @param Response $response
     * @return array
     */
    private function extractResponseLaravel($response)
    {
        $body = $response->content();

        return [
            'status_code' => $response->status(),
            'body_length' => strlen($body),
            'body' => base64_encode(substr($body, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($response->headers->all()),
        ];
    }
}
