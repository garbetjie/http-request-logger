<?php

namespace Garbetjie\RequestLogging\Http;

use Garbetjie\RequestLogging\Http\Middleware\OutgoingRequestLoggingMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerGuzzleHandlerStackIfNotRegistered();
        $this->registerGuzzleClientIfNotRegistered();
        $this->registerGuzzleClientInterfaceIfNotRegistered();
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
