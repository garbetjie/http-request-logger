# Changelog

* **5.1.0**
    * Add request logging for the Laravel HTTP client facade (only from Laravel >= 8.45).

* **5.0.1**
    * Fix incorrect service provider name in `composer.json`.

* **5.0.0**
    * Refactor the way the logging happens to ensure customisation is easy to implement.

* **4.2.1**
    * Remove open-ended PHP version requirements.
    * Ensure an array of options is passed to `Garbetjie\RequestLogging\Http\SoapClient`.

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
