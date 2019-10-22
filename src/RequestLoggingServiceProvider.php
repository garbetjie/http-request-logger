<?php

namespace Garbetjie\Http\RequestLogging;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use function config;

class RequestLoggingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'garbetjie-http-request-logging');

        $this->registerGuzzleClient();
        $this->registerGuzzleClientInterface();
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
                $middleware = $container->make(RequestLoggingMiddleware::class);
                /* @var RequestLoggingMiddleware $middleware */

                $handler = $client->getConfig('handler');
                /* @var HandlerStack $handler */

                $handler->push($middleware, 'logging');

                return new Client(['handler' => $handler] + $client->getConfig());
            }
        );
    }
}
