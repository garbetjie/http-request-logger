HTTP Request Logging
--------------------

An HTTP request logger, that makes it really easy to log incoming & outgoing requests and responses.

This library easily enables you to be able to log outgoing requests (using [Guzzle](http://docs.guzzlephp.org) middleware),
as well as incoming requests using the bundled PSR-compliant middleware that also works with Laravel.


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

use Garbetjie\Http\RequestLogging\RequestLoggingMiddleware;

class Kernel
{
    // ...
    protected $middleware = [
        RequestLoggingMiddleware::class,
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
$app->add(Garbetjie\Http\RequestLogging\RequestLoggingMiddleware::class);
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
$stack->push(new Garbetjie\Http\RequestLogging\RequestLoggingMiddleware($logger, 'debug'), 'logging');
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

* The bodies of each are Base64-encoded, and are truncated to a maximum length (16,384 bytes by default).
* Headers are reduced to strings (multiple values for a header are `imploded` with a `, ` separator), and the header names
  are lower-cased and hyphenated. 

The following is an example of what is provided with the default logging context:

    // Request
    [
        'method' => 'POST',
        'url' => 'https://example.org',
        'body_length' => 12,
        'body' => 'eyJjb3ciOiJtb299',
        'headers' => [
            'content-type' => 'application/json',
            'host' => 'example.org'
        ]
    ];
    
    // Response
    [
        'status_code' => 200,
        'body_length' => 1256,
        'body' => 'PCFkb2N0eXBlIGh0bWw+CjxodG1sPgo8aGVhZD4KICAgIDx0aXRsZT5FeGFtcGxlIERvbWFpbjwvdGl0b...',
        'headers' => [
            'content-type' => 'text/html; charset=utf8',
            'server' => 'EOS (vny006/0452)',
        ]
    ];


### Customising logging context

If you require the context to be different to what is provided by default, you can customise the logging context that is
extracted.

Simply create a context extractor that implements `Garbetjie\Http\RequestLogging\ContextExtractorInterface`, and set the
request / response extractors to use it. For example:

```php
<?php

class EmptyContextExtractor implements Garbetjie\Http\RequestLogging\ContextExtractorInterface
{
    public function extract($from) : array
    {
        return [];
    }
}

/* @var Psr\Log\LoggerInterface $logger */

$middleware = new Garbetjie\Http\RequestLogging\RequestLoggingMiddleware($logger, 'debug');
$middleware->setRequestContextExtractor(new EmptyContextExtractor());
$middleware->setResponseContextExtractor(new EmptyContextExtractor());

```

## Changelog

* **1.2.0**
    * Convert context extractors to use a shared interface.
    * Reduce required PHP version to 7.0, instead of 7.2.
    * Add config publishing to the Laravel service provider. 
    * Add examples of contexts.
