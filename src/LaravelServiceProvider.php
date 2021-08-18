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
use WeakReference;

class LaravelServiceProvider extends ServiceProvider
{
	/**
	 * Register classes for use.
	 */
    public function register()
    {
    	$this->registerGuzzleHandlerStack();
    	$this->extendGuzzleHandlerStack();
        $this->registerGuzzleClient();
        $this->registerGuzzleClientInterface();
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
				$requestEntryMap[$event->request] = $logger->request($event->request->toPsrRequest(), $logger::DIRECTION_OUT);
			}
		);

		Event::listen(
			ResponseReceived::class,
			static function (ResponseReceived $event) use ($logger, $requestEntryMap) {
				$logger->response($requestEntryMap[$event->request], $event->response->toPsrResponse());

				unset($requestEntryMap[$event->request]);
			}
		);
	}

    protected function registerGuzzleClientInterface()
    {
        $this->app->bindIf(ClientInterface::class, Client::class);
    }

    protected function registerGuzzleClient()
    {
        $this->app->bindIf(
            Client::class,
            function (Container $container) {
                return new Client(['handler' => $container->make(HandlerStack::class)]);
            }
        );
    }

    protected function registerGuzzleHandlerStack()
    {
        $this->app->bindIf(
            HandlerStack::class,
            function (Container $container) {
            	return HandlerStack::create();
            }
        );
    }

    protected function extendGuzzleHandlerStack()
	{
		$this->app->extend(
			HandlerStack::class,
			function (HandlerStack $stack, Container $container) {
				$stack->push($container->make(OutgoingRequestLoggingMiddleware::class), 'garbetjie/http-request-logger');

				return $stack;
			}
		);
	}
}
