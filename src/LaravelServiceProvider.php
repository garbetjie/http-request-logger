<?php

namespace Garbetjie\Http\RequestLogging;

use Garbetjie\Http\RequestLogging\Middleware\GuzzleMiddleware;
use Garbetjie\Http\RequestLogging\Middleware\LaravelMiddleware;
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

        $this->registerGuzzleClient();
        $this->registerGuzzleClientInterface();
        $this->registerMiddleware();
    }

    protected function registerMiddleware()
    {
        foreach ([LaravelMiddleware::class, GuzzleMiddleware::class] as $className) {
            $this->app
                ->when($className)
                ->needs('$level')
                ->give(config('garbetjie-http-request-logging.level'));
        }
    }

    protected function registerGuzzleClientInterface()
    {
        $this->app->alias(Client::class, ClientInterface::class);
    }

    protected function registerGuzzleClient()
    {
        $this->app->extend(
            Client::class,
            function (Client $client, Container $container) {
                $middleware = $container->make(GuzzleMiddleware::class);
                /* @var GuzzleMiddleware $middleware */

                $handler = $client->getConfig('handler');
                /* @var HandlerStack $handler */

                // Replace the HTTP request logging middleware on the handler stack. We remove it first, so that it isn't
                // accidentally added multiple times.
                $handler->remove('garbetjie-http-request-logging');
                $handler->push($middleware, 'garbetjie-http-request-logging');

                return new Client(['handler' => $handler] + $client->getConfig());
            }
        );
    }
}
