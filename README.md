HTTP Request Logger
--------------------

An HTTP request logging library that makes it simple to  easy to implement the logging of all HTTP requests & responses.

Works with [Laravel](https://laravel.com/docs), [Guzzle](http://docs.guzzlephp.org) and PHP's `SoapServer` and
`SoapClient` classes.

![](https://github.com/garbetjie/http-request-logger/actions/workflows/tests.yaml/badge.svg)

# Table of contents

* [Introduction](#introduction)
* [Installation](#installation)
* [Usage](#usage)
    * [Incoming requests: Laravel](#incoming-requests-in-laravel)
    * [Incoming requests: PSR-15 compliant frameworks](#incoming-requests-in-psr-15-compliant-frameworks)
    * [Incoming requests: SOAP server](#incoming-soap-requests)
    * [Outgoing requests: Laravel](#outgoing-requests-in-laravel)
    * [Outgoing requests: Guzzle](#outgoing-requests-through-guzzle)
    * [Outgoing requests: SOAP](#outgoing-soap-requests)
* [Customisation](#customisation)
    * [Context](#logging-context)
    * [Request & response IDs](#id-generation)
    * [Toggling logging](#toggling-logging)
* [Changelog](#changelog)

# Introduction

It is often quite useful to be able to see all requests & responses generated by your application - especially whilst
in development. This library makes it trivial to log all incoming & outgoing requests & responses using
[Monolog](https://packagist.org/packages/monolog/monolog).

By default, all requests & responses are logged at the `debug` log level. Sensible defaults for logging context are used,
and headers that may contain sensitive values (such as `Cookie`, `Set-Cookie` and `Authorization`) have their values
obfuscated. Each request & corresponding response are linked together through a unique ID. This allows you to interrogate
your logs to find the matching response for any given request.

# Installation

```shell
composer require garbetjie/http-request-logger
```

# Usage

## Incoming requests in Laravel

Enabling request logging in Laravel is as simple as adding the middleware to the `$middleware` property on your
`App\Http\Kernel` class. The example shown below is enough to ensure that all incoming requests & outgoing responses are
logged in Laravel:

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ...
    protected $middleware = [
        \Garbetjie\RequestLogging\Http\Middleware\IncomingRequestLoggingMiddleware::class
    ];
    // ...
} 
```

> **Take note:** You should add the request logging middleware to your application's global middleware stack, and
> determine when logging should take place in a service provider. When added to a middleware group, any uncaught
> exceptions thrown will prevent the request & response from being logged.
> 
> See the [Toggle enabled/disabled](#toggle-enableddisabled) section for more information on this.

## Incoming requests in PSR-15 compliant frameworks

If you are not using Laravel, but are making use of a framework that adheres to [PSR-15](https://www.php-fig.org/psr/psr-15),
you'll need to be a little more explicit in creating the middleware. The example below makes use of the Slim framework
as an example:

```php
<?php

$app = Slim\Factory\AppFactory::create();
$app->add(\Garbetjie\RequestLogging\Http\Middleware\IncomingRequestLoggingMiddleware::class);
$app->run();
```

## Incoming SOAP requests

Logging incoming SOAP requests is trivial. Instead of creating a new `\SoapServer` instance, simply create a new instance
of `\Garbetjie\RequestLogging\Http\SoapServer`. You can use this new instance exactly the same as you would with an
instance of `\SoapServer`. Example shown below:

```php
<?php

$monolog = new Monolog\Logger('logger');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);
$server = new Garbetjie\RequestLogging\Http\SoapServer($logger, '/path/to/wsdl/or/null');
$server->setObject(new stdClass());
$server->handle();;
```

## Outgoing requests in Laravel

WHen using Laravel, you can simply type-hint either `GuzzleHttp\ClientInterface` or `GuzzleHttp\Client` wherever
Laravel performs dependency injection. This package includes a service provider that automatically adds the middleware
to a handler stack for you.

An example of using this type-hinting in a controller is shown below:

```php
<?php

namespace App\Http\Controllers;

class MyController
{
    public function myAction(\GuzzleHttp\ClientInterface $client)
    {
        return $client->request('GET', 'https://example.org')->getBody()->getContents();
    }
}
```

## Outgoing requests through Guzzle

Outbound request logging happens through Guzzle middleware. In order to log outbound requests, simply add an instance
of the `OutgoingRequestLoggingMiddleware` to your handler stack. Ideally, this should be the last middleware in your
handler stack in order to ensure the logged representation of the request is as accurate as possible.

```php
<?php

$monolog = new Monolog\Logger('logger');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);

$stack = GuzzleHttp\HandlerStack::create();
$stack->push(new Garbetjie\RequestLogging\Http\Middleware\OutgoingRequestLoggingMiddleware($logger));
$client = new GuzzleHttp\Client(['stack' => $stack]);

$client->request('GET', 'https://example.org');
```

## Outgoing SOAP requests

In order to log outbound SOAP requests, simply create your Guzzle client as if you be logging any other outgoing HTTP
request, and pass this client instance to a new instance of `Garbetjie\RequestLogging\Http\SoapClient`. You can use
this SOAP client instance just as if you were making use of a native `SoapClient` instance:

```php
<?php

/* @var GuzzleHttp\Client $guzzleClient */

$soapClient = new Garbetjie\RequestLogging\Http\SoapClient($guzzleClient, null, []);
$soapResponse = $soapClient->MyCustomSoapMethod(['parameters']);
```

# Customisation

All aspects of the request & response logging are customisable. This includes the ID used to link a request & response
together, the log message, as well as the context logged with each request & response.

## Logging context

By default, incoming & outgoing requests generate a context with a similar structure to below.
Sensitive headers such as `Authorization` and `Cookie` have their values replaced with `***`:

```php
<?php

$context = [
    'id' => '',  // string - contains the unique ID that links this request to its response.
    'method' => '',  // string - upper-cased request method.
    'url' => '',  // string - URL to which the request was sent.
    'body_length' => 0,  // integer - size of the body sent.
    'body' => base64_encode(''),  // string - base64-encoded body sent in the request.
    'headers' => [],  // array<string, string> - array of headers sent in the request. Names are normalized and lower-cased.
];
```

The context shown below indicates the default structure of the logging context for responses.
Any `Set-Cookie` headers have their values stripped out and replaced with `***`.

```php
<?php

$context = [
    'id' => '',  // string - contains the unique ID that links this response to the request that created it.
    'duration' => 0.0,  // float - the duration of the request, in seconds (with fractional milliseconds).
    'status_code' => 0,  // integer - the HTTP status code returned in the response.
    'body_length' => 0,  // integer - the size of the body sent.
    'body' => base64_encode(''),  // string - base64-encoded body sent in the response.
    'headers' => [],  // array<string, string> - array of headers sent in the response. Names are normalized and lower-cased.
];
```

It is quite simple to customise the logging context that is generated:

```php
<?php

$monolog = new Monolog\Logger('name');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);

$logger->context(
    function (Garbetjie\RequestLogging\Http\RequestEntry $entry): array {
        // Return an array containing the context to log.
        
        return [];
    },
    function (Garbetjie\RequestLogging\Http\ResponseEntry $entry): array {
        // Return an array containing the context to log.
    
        return [];
    }
);
```

If you'd like to simply extend off the context that is created by default, you can reuse the context extractors that are
already available:

```php
<?php

$monolog = new Monolog\Logger('name');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);

$logger->context(
    function (Garbetjie\RequestLogging\Http\RequestEntry $entry): array {
        $context = (new Garbetjie\RequestLogging\Http\Context\RequestContext())($entry);
        
        $body = base64_decode($context['body']);
        // Modify $body.
        $context['body'] = base64_encode($body);
        
        return $context;
    },
    null
);
```

## ID generation.

By default, the ID generation for linking requests & responses together makes use of `endyjasmi/cuid` to generate a full
CUID.

This can be easily customised by providing a callable that simply returns a string containing the ID to use:

```php
<?php

$monolog = new Monolog\Logger('name');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);

$logger->id(
    function(): string {
        return base64_encode(random_bytes(8));
    }
);
```

## Toggling logging

By default, all requests & responses are logged. However, it is possible to toggle whether a request or response should
be logged. Simply provide either a boolean value, or a callable that returns a boolean value indicating whether or not
logging should be enabled for the given request:

```php
<?php

$monolog = new Monolog\Logger('name');
$logger = new Garbetjie\RequestLogging\Http\Logger($monolog);

function shouldLog($request) {
    if ($request instanceof Symfony\Component\HttpFoundation\Request) {
        return stripos($request->getUri(), 'https://example.org') === false;
    } elseif ($request instanceof Psr\Http\Message\RequestInterface) {
        return stripos((string)$request->getUri(), 'https://example.org') === false;
    } else {
        return false;
    }
}

$logger->enabled(
    function(Garbetjie\RequestLogging\Http\RequestEntry $entry): bool {  // Provide a callable that returns a boolean.
        return shouldLog($entry->request());
    },
    function(Garbetjie\RequestLogging\Http\ResponseEntry $entry): bool {
        return shouldLog($entry->request());
    }
);

// Alternatively:
$logger->enabled(false, true);
$logger->enabled(
    new Garbetjie\RequestLogging\Http\Context\RequestContext(),
    new Garbetjie\RequestLogging\Http\Context\ResponseContext()
);
```

# Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full changelog.