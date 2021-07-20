<?php

namespace Garbetjie\RequestLogging\Http;

use Garbetjie\RequestLogging\Http\Middleware\OutgoingRequestLoggingMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SplObjectStorage;

class LaravelServiceProvider extends ServiceProvider
{
	/**
	 * Register classes for use.
	 */
    public function register()
    {
        $this->registerGuzzleHandlerStackIfNotRegistered();
        $this->registerGuzzleClientIfNotRegistered();
        $this->registerGuzzleClientInterfaceIfNotRegistered();
    }

	/**
	 * Run at framework boot.
	 *
	 */
    public function boot()
	{
		// If the event classes exist, then register the event listeners.
		if (class_exists(RequestSending::class) && class_exists(ResponseReceived::class)) {
			$this->bootHttpClientEvents();
		}
	}

	/**
	 * Listens for HTTP client events to log requests & responses.
	 *
	 */
	protected function bootHttpClientEvents()
	{
		$logger = $this->app[Logger::class];
		/* @var Logger $logger */

		// Create a mapping between requests & request entries.
		$requestEntryMap = new SplObjectStorage();

		Event::listen(
			RequestSending::class,
			static function(RequestSending $event) use ($logger, $requestEntryMap) {
				$requestEntryMap[$event->request] = $logger->request($event->request);
			}
		);

		Event::listen(
			ResponseReceived::class,
			static function (ResponseReceived $event) use ($logger, $requestEntryMap) {
				$logger->response($requestEntryMap[$event->request], $event->response);
			}
		);
	}

    protected function registerGuzzleClientInterfaceIfNotRegistered()
    {
        $this->app->bindIf(ClientInterface::class, Client::class);
    }

    protected function registerGuzzleClientIfNotRegistered()
    {
        $this->app->bindIf(
            Client::class,
            function (Container $container) {
                return new Client(['handler' => $container->make(HandlerStack::class)]);
            }
        );
    }

    protected function registerGuzzleHandlerStackIfNotRegistered()
    {
        $this->app->bindIf(
            HandlerStack::class,
            function (Container $container) {
                $middleware = $container->make(OutgoingRequestLoggingMiddleware::class);

                $stack = HandlerStack::create();
                $stack->push($middleware, 'garbetjie/http-request-logger');

                return $stack;
            }
        );
    }
}
