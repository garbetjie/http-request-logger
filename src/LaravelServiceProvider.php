<?php

namespace Garbetjie\Http\RequestLogging;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use function config;
use function config_path;

class LaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'garbetjie-http-request-logging');

        $this->publishes([
            __DIR__ . '/../config.php' => config_path('garbetjie-http-request-logging.php'),
        ]);

        $this->registerGuzzleHandlerStackIfNotRegistered();
        $this->registerGuzzleClientIfNotRegistered();
        $this->registerGuzzleClientInterfaceIfNotRegistered();
        $this->registerMiddleware();
    }

    protected function registerMiddleware()
    {
        foreach ([IncomingRequestLoggingMiddleware::class, OutgoingRequestLoggingMiddleware::class] as $className) {
            $this->app
                ->when($className)
                ->needs('$level')
                ->give(config('garbetjie-http-request-logging.level'));
        }
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
                $stack->push($middleware, 'garbetjie-http-request-logging');

                return $stack;
            }
        );
    }
}
