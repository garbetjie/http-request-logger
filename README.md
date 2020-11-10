HTTP Request Logging
--------------------

An HTTP request logger, that makes it really easy to log incoming & outgoing requests and responses.

This library easily enables you to be able to log outgoing requests (using [Guzzle](http://docs.guzzlephp.org) middleware),
as well as incoming requests using the bundled PSR-compliant middleware that also works with Laravel.

![](https://travis-ci.com/garbetjie/http-request-logger.svg?branch=master)


## Table of contents

* [Installation](#installation)
* [Log incoming requests](#log-incoming-requests)
    * [In Laravel](#laravel)
    * [PSR-15 compliant frameworks](#psr-15)
* [Log outgoing requests](#log-outgoing-requests)
* [Log SOAP requests](#log-soap-requests)
* [Logging contexts](#logging-context)
    * [Default logging context](#default-logging-context)
    * [Customising logging context](#customising-logging-context)
* [Enabling or disabling logging](#toggling-logging)
* [Changelog](#changelog)

## Installation

    composer require garbetjie/http-request-logger
    
## Log incoming requests

### Laravel

When using Laravel, simply add the HTTP request logging middleware to your application's middleware stack.
This can be done by adding the middleware to your `$middleware` property in your HTTP Kernel:

```php
<?php

// app/Http/Kernel.php:

use Garbetjie\Http\RequestLogging\IncomingRequestLoggingMiddleware;

class Kernel
{
    // ...
    protected $middleware = [
        IncomingRequestLoggingMiddleware::class,
    ];
    // ...
}
```

### PSR-15

If you are not using Laravel, but are using a framework that makes use of [PSR-15](https://www.php-fig.org/psr/psr-15),
then the same middleware object can be used. The example below makes use of the Slim framework:

```php
<?php

$app = Slim\Factory\AppFactory::create();
$app->add(Garbetjie\Http\RequestLogging\IncomingRequestLoggingMiddleware::class);
$app->run();
```


## Log outgoing requests

If you're using Laravel, you can simply type-hint `GuzzleHttp\ClientInterface` or `GuzzleHttp\Client` wherever Laravel
performs dependency injection, and you will receive an instance of the Guzzle client that contains the logging middleware:

```php
<?php

class MyController
{
    public function index(\GuzzleHttp\ClientInterface $client)
    {
        $client->request('GET', 'https://example.org');
    }
}
```

If you're making use of Guzzle anywhere else, it is still easy to use this middleware in Guzzle:

```php
<?php

/* @var Psr\Log\LoggerInterface $logger */

$stack = GuzzleHttp\HandlerStack::create();
$stack->push(new Garbetjie\Http\RequestLogging\OutgoingRequestLoggingMiddleware($logger, 'debug'), 'logging');
$client = new GuzzleHttp\Client(['stack' => $stack]);
```


## Log SOAP requests

Logging outgoing SOAP requests is a simple task. Simply create an instance of `Garbetjie\Http\RequestLogging\SoapClient`,
and use it as you would the normal SOAP client:

```php
<?php

/* @var Psr\Log\LoggerInterface $logger */
/* @var GuzzleHttp\Client $client */

$soapClient = new Garbetjie\Http\RequestLogging\SoapClient($client, 'Path to WSDL');
$soapResponse = $soapClient->ExecuteSoapMethodCall([]);
```


## Logging context

All logging contexts (both default and customised) have the following properties appended to them. At this point, this
cannot be customised:

    // Requests
    ['id' => generate_id()]
    
    // Responses
    ['id' => generate_id(), 'duration' => 0.0]

An explanation of the properties:

* `id`: A unique ID that links a request and response together in the log messages.
* `duration`: The duration of the request (appended to response messages only).


### Default logging context

By default, the following logging context is extracted from requests and responses:

    // Requests
    ['method' => '', 'url' => '', 'body_length' => 0, 'body' => '', 'headers' => []]
    
    // Responses
    ['status_code' => 0, 'body_length' => 0, 'body' => '', 'headers' => []]

* The bodies of each are truncated to a maximum length (16,384 bytes by default) and then base64 encoded.
* Headers are reduced to strings (multiple values for a header are `imploded` with a `, ` separator), and the header names
  are lower-cased and hyphenated. 
* Sensitive headers (`Authorization` and `Set-Cookie` currently) are obfuscated, and their values are replaced with `***`.

The following is an example of what is provided with the default logging context:

    // Request
    [
        'id' => 'f8a8f39f',
        'method' => 'POST',
        'url' => 'https://example.org',
        'body_length' => 12,
        'body' => 'eyJjb3ciOiJtb299',
        'headers' => [
            'content-type' => 'application/json',
            'host' => 'example.org',
            'authorization' => '***',
        ]
    ];
    
    // Response
    [
        'id' => 'f8a8f39f',
        'duration' => 0.125,
        'status_code' => 200,
        'body_length' => 1256,
        'body' => 'PCFkb2N0eXBlIGh0bWw+CjxodG1sPgo8aGVhZD4KICAgIDx0aXRsZT5FeGFtcGxlIERvbWFpbjwvdGl0b...',
        'headers' => [
            'content-type' => 'text/html; charset=utf8',
            'server' => 'EOS (vny006/0452)',
            'set-cookie' => '***',
        ]
    ];


### Customising logging context

If you require the context to be different to what is provided by default, you can customise the logging context that is
extracted. This is especially useful if you want to strip out `Authorization` headers, `Set-Cookie` headers, or
anonymize the values of certain headers.

Simply create a context extractor that is a `callable`, and set the request / response extractors to use it.
For example:

```php
<?php

class EmptyContextExtractor
{
    public function __invoke($from) : array
    {
        return [];
    }
}

/* @var Psr\Log\LoggerInterface $logger */

$middleware = new \Garbetjie\Http\RequestLogging\IncomingRequestLoggingMiddleware($logger, 'debug');
$middleware->requestContextExtractor(new EmptyContextExtractor());
$middleware->responseContextExtractor(new EmptyContextExtractor());

// or
$middleware->requestContextExtractor(
    function($request, $direction) {
        return [];
    }
);

$middleware->responseContextExtractor(
    function($response, $request, $direction) {
        return [];
    }
);
```


## Toggling logging

Sometimes it might not be desirable to log all requests and responses. For example, you might not want to log every
incoming request that is generated by a health check.

Logging for requests and/or responses can be easily toggled using the `enable{Request,Response}Logging()` methods. If a
callable is given to this method, it should return a boolean value that indicates whether the request/response
should be logged: 

```php
<?php

/* @var Psr\Log\LoggerInterface $logger */

$middleware = new \Garbetjie\Http\RequestLogging\IncomingRequestLoggingMiddleware($logger, 'debug');

$middleware->enableRequestLogging(
    function ($request, $direction) {
        return true;
    }
);

// The above is equivalent to the following:
$middleware->enableRequestLogging(true);

$middleware->enableResponseLogging(
    function ($response, $request, $direction) {
        return false;
    }
);

// The above is equivalent to the following:
$middleware->enableResponseLogging(false);
```

## Changelog

* **4.2.0**
    * Add `enableRequestLogging()`, `enableResponseLogging()`, `requestContextExtractor()` and `responseContextExtractor()`
      methods.
    * Set default context extractors to the "safe" versions that strip out `Authorization` and `Set-Cookie` headers.
      
* **4.1.0**
    * Refactor the `with{Deciders,Messages,Extractors}` to be `set{Deciders,Messages,Extractors}`. Backwards compatibility maintained.
    * Add `Safe{Request,Response}ContextExtractor` extractors that replace the values of sensitive headers
      (`set-cookie`, `cookie`, and `authorization` headers).

* **4.0.0**
    * Add tests for Laravel 8.0.
    * Remove references to `GuzzleHttp\Client::getConfig()` (<https://github.com/guzzle/guzzle/pull/2516>).
    * Update Laravel service provider registration to no longer extend the client.

* **3.1.0**
    * Fix bug where Guzzle request logging was logging incorrect duration, and was prevent requests from being executed
      concurrently.

* **3.0.1**
    * Move Laravel service provider.
    * Fix path to default config file.
    
* **3.0.0**
    * Refactor structure of middleware, separating them into incoming & outgoing request middleware.

* **2.2.1**
    * Fix incorrect path to config file in Laravel service provider.

* **2.2.0**
    * Deprecate simpler class names for middleware, and start using more explicit class names.
    * Change to using [cuid's](https://github.com/endyjasmi/cuid) for request/response ID generation.

* **2.1.0**
    * Add request/response direction to deciders and context extractors.
    
* **2.0.1**
    * Add additional aliases for Guzzle middleware in `LaravelServiceProvider`.

* **2.0.0**
    * Split out middleware for different frameworks (no longer in a single class).
    * Add unit tests.
    * Change to using callables for context extraction instead of interfaces.
    * Add logging deciders, which will determine whether or not a request or response is logged.
    
* **1.2.3**
    * Reference base Symfony response for ResponseContextExtractor.

* **1.2.2**
    * Fix bug causing request and response bodies to not be rewound.

* **1.2.1**
    * Add missing Base64-encoding of request body.
    
* **1.2.0**
    * Convert context extractors to use a shared interface.
    * Reduce required PHP version to 7.0, instead of 7.2.
    * Add config publishing to the Laravel service provider. 
    * Add examples of contexts.
