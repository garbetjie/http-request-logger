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

    public function returnValueWhenCustomising(): array
    {
        return [
            'id' => ['id', [function() { }]],
            'context' => ['context', [null, null]],
            'message' => ['message', [function() { }]],
            'enabled' => ['enabled', [true, true]]
        ];
    }

    public function messageCanBeCustomised(): array
    {
        return [
            'incoming request' => [Logger::DIRECTION_IN, 'request:in', 'response:out'],
            'outgoing request' => [Logger::DIRECTION_OUT, 'request:out', 'response:in'],
        ];
    }

    public function enabledCanBeCustomised(): array
    {
        $fnTrue = function() {
            return true;
        };

        $fnFalse = function() {
            return false;
        };

        $message = function(string $what, string $direction) {
            return "{$what}:{$direction}";
        };

        return [
            [true, true, Logger::DIRECTION_IN, $message, ['request:in', 'response:out']],
            [true, false, Logger::DIRECTION_IN, $message, ['request:in']],
            [false, true, Logger::DIRECTION_IN, $message, ['response:out']],
            [false, false, Logger::DIRECTION_IN, $message, []],

            [true, true, Logger::DIRECTION_OUT, $message, ['request:out', 'response:in']],
            [true, false, Logger::DIRECTION_OUT, $message, ['request:out']],
            [false, true, Logger::DIRECTION_OUT, $message, ['response:in']],
            [false, false, Logger::DIRECTION_OUT, $message, []],

            [$fnTrue, $fnTrue, Logger::DIRECTION_IN, $message, ['request:in', 'response:out']],
            [$fnTrue, $fnFalse, Logger::DIRECTION_IN, $message, ['request:in']],
            [$fnFalse, $fnTrue, Logger::DIRECTION_IN, $message, ['response:out']],
            [$fnFalse, $fnFalse, Logger::DIRECTION_IN, $message, []],

            [$fnTrue, $fnTrue, Logger::DIRECTION_OUT, $message, ['request:out', 'response:in']],
            [$fnTrue, $fnFalse, Logger::DIRECTION_OUT, $message, ['request:out']],
            [$fnFalse, $fnTrue, Logger::DIRECTION_OUT, $message, ['response:in']],
            [$fnFalse, $fnFalse, Logger::DIRECTION_OUT, $message, []],
        ];
    }

    public function contextCanBeCustomised(): array
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
                Logger::DIRECTION_IN,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createSymfonyRequest(), $response = $this->createSymfonyResponse(),
                Logger::DIRECTION_OUT,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createPsrServerRequest(), $response = $this->createPsrResponse(),
                Logger::DIRECTION_IN,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createPsrServerRequest(), $response = $this->createPsrResponse(),
                Logger::DIRECTION_OUT,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createPsrRequest(), $response = $this->createPsrResponse(),
                Logger::DIRECTION_IN,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createPsrRequest(), $response = $this->createPsrResponse(),
                Logger::DIRECTION_OUT,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'out', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'in', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],

            [
                $request = $this->createStringRequest(), $response = $this->createStringResponse(),
                Logger::DIRECTION_IN,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'in', 'request' => $request],
                ['what' => 'response', 'direction' => 'out', 'request' => $request, 'response' => $response]
            ],
            [
                $request = $this->createStringRequest(), $response = $this->createStringResponse(),
                Logger::DIRECTION_OUT,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'out', 'request' => $request],
                ['what' => 'response', 'direction' => 'in', 'request' => $request, 'response' => $response]
            ],
        ];
    }

    public function requestResponseAndDirection(): array
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

    public function messageCallableArguments(): array
    {
        return [
            [Logger::DIRECTION_IN],
            [Logger::DIRECTION_OUT],
        ];
    }
}
