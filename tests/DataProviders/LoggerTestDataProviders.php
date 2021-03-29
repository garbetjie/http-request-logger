<?php

namespace Garbetjie\Http\RequestLogging\Tests\DataProviders;

use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\RequestLogEntry;
use Garbetjie\Http\RequestLogging\ResponseLogEntry;
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
            'message' => ['message', [function() { }, function() { }]],
            'enabled' => ['enabled', [true, true]]
        ];
    }

    public function correctArgumentsAreSuppliedToCustomisingCallables(): array
    {
        return [
            ['context', 2],
            ['message', 1],
            ['enabled', 2],
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

        $reqMessage = function(RequestLogEntry $entry) {
            return "request:{$entry->direction()}";
        };

        $resMessage = function (ResponseLogEntry $entry) {
            return "response:{$entry->direction()}";
        };

        return [
            [true, true, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['request:in', 'response:out']],
            [true, false, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['request:in']],
            [false, true, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['response:out']],
            [false, false, Logger::DIRECTION_IN, $reqMessage, $resMessage, []],

            [true, true, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['request:out', 'response:in']],
            [true, false, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['request:out']],
            [false, true, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['response:in']],
            [false, false, Logger::DIRECTION_OUT, $reqMessage, $resMessage, []],

            [$fnTrue, $fnTrue, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['request:in', 'response:out']],
            [$fnTrue, $fnFalse, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['request:in']],
            [$fnFalse, $fnTrue, Logger::DIRECTION_IN, $reqMessage, $resMessage, ['response:out']],
            [$fnFalse, $fnFalse, Logger::DIRECTION_IN, $reqMessage, $resMessage, []],

            [$fnTrue, $fnTrue, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['request:out', 'response:in']],
            [$fnTrue, $fnFalse, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['request:out']],
            [$fnFalse, $fnTrue, Logger::DIRECTION_OUT, $reqMessage, $resMessage, ['response:in']],
            [$fnFalse, $fnFalse, Logger::DIRECTION_OUT, $reqMessage, $resMessage, []],
        ];
    }

    public function contextCanBeCustomised(): array
    {
        $requestExtractor = function(RequestLogEntry $logEntry) {
            $request = $logEntry->request();

            return [
                'what' => 'request',
                'direction' => $logEntry->direction(),
                'request' => is_string($request) ? $request : spl_object_hash($request),
            ];
        };

        $responseExtractor = function(ResponseLogEntry $logEntry) {
            $request = $logEntry->request();
            $response = $logEntry->response();

            return [
                'what' => 'response',
                'direction' => $logEntry->direction(),
                'request' => is_string($request) ? $request : spl_object_hash($request),
                'response' => is_string($response) ? $response : spl_object_hash($response),
            ];
        };

        return [
            [
                $request = $this->createLaravelRequest(), $response = $this->createLaravelResponse(),
                Logger::DIRECTION_IN,
                $requestExtractor, $responseExtractor,
                ['what' => 'request', 'direction' => 'in', 'request' => spl_object_hash($request)],
                ['what' => 'response', 'direction' => 'out', 'request' => spl_object_hash($request), 'response' => spl_object_hash($response)]
            ],
            [
                $request = $this->createLaravelRequest(), $response = $this->createLaravelResponse(),
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
            [$this->createLaravelRequest(), $this->createLaravelResponse(), Logger::DIRECTION_IN],
            [$this->createLaravelRequest(), $this->createLaravelResponse(), Logger::DIRECTION_OUT],

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

    public function startedAtTrackingIsAlwaysEmptied(): array
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false]
        ];
    }
}
