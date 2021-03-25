<?php

namespace Garbetjie\Http\RequestLogging\Tests\DataProviders;

use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use function is_string;
use function spl_object_hash;

class LoggerTestDataProviders
{
    use CreatesRequests;
    use CreatesResponses;

    public function requestsAndResponsesCanBeToggled(): array
    {
        $fnTrue = function() {
            return true;
        };

        $fnFalse = function() {
            return false;
        };

        return [
            [true, true, Logger::DIRECTION_IN, 2],
            [true, false, Logger::DIRECTION_IN, 1],
            [false, true, Logger::DIRECTION_IN, 1],
            [false, false, Logger::DIRECTION_IN, 0],

            [true, true, Logger::DIRECTION_OUT, 2],
            [true, false, Logger::DIRECTION_OUT, 1],
            [false, true, Logger::DIRECTION_OUT, 1],
            [false, false, Logger::DIRECTION_OUT, 0],

            [$fnTrue, $fnTrue, Logger::DIRECTION_IN, 2],
            [$fnTrue, $fnFalse, Logger::DIRECTION_IN, 1],
            [$fnFalse, $fnTrue, Logger::DIRECTION_IN, 1],
            [$fnFalse, $fnFalse, Logger::DIRECTION_IN, 0],

            [$fnTrue, $fnTrue, Logger::DIRECTION_OUT, 2],
            [$fnTrue, $fnFalse, Logger::DIRECTION_OUT, 1],
            [$fnFalse, $fnTrue, Logger::DIRECTION_OUT, 1],
            [$fnFalse, $fnFalse, Logger::DIRECTION_OUT, 0],
        ];
    }

    public function extractorsCanBeCustomised(): array
    {
        $requestExtractor = function($request, $direction) {
            return [
                'what' => 'request',
                'direction' => $direction,
                'request' => is_string($request) ? $request : spl_object_hash($request),
            ];
        };

        $responseExtractor = function($response, $request, $direction) {
            return [
                'what' => 'response',
                'direction' => $direction,
                'request' => is_string($request) ? $request : spl_object_hash($request),
                'response' => is_string($response) ? $response : spl_object_hash($response),
            ];
        };

        return [
            [
                $request = $this->createSymfonyRequest(), $response = $this->createSymfonyResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_IN,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createSymfonyRequest(), $response = $this->createSymfonyResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_OUT,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createPsrServerRequest(), $response = $this->createPsrResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_IN,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createPsrServerRequest(), $response = $this->createPsrResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_OUT,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createPsrRequest(), $response = $this->createPsrResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_IN,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createPsrRequest(), $response = $this->createPsrResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_OUT,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createStringRequest(), $response = $this->createStringResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_IN,
                ['what' => 'request', 'direction' => 'in', 'request' => $request],
                ['what' => 'response', 'direction' => 'out', 'request' => $request, 'response' => $response]
            ],
            [
                $request = $this->createStringRequest(), $response = $this->createStringResponse(),
                $requestExtractor, $responseExtractor,
                Logger::DIRECTION_OUT,
                ['what' => 'request', 'direction' => 'out', 'request' => $request],
                ['what' => 'response', 'direction' => 'in', 'request' => $request, 'response' => $response]
            ],
        ];
    }

    public function requestsAndResponsesAreLoggedCorrectly(): array
    {
        return [
            [$this->createSymfonyRequest(), $this->createSymfonyResponse(), Logger::DIRECTION_IN],
            [$this->createSymfonyRequest(), $this->createSymfonyResponse(), Logger::DIRECTION_OUT],

            [$this->createPsrServerRequest(), $this->createPsrResponse(), Logger::DIRECTION_IN],
            [$this->createPsrServerRequest(), $this->createPsrResponse(), Logger::DIRECTION_OUT],

            [$this->createPsrRequest(), $this->createPsrResponse(), Logger::DIRECTION_IN],
            [$this->createPsrRequest(), $this->createPsrResponse(), Logger::DIRECTION_OUT],

            [$this->createStringRequest(), $this->createStringResponse(), Logger::DIRECTION_IN],
            [$this->createStringRequest(), $this->createStringResponse(), Logger::DIRECTION_OUT],
        ];
    }
}
