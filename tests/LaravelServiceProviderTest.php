<?php

namespace Garbetjie\RequestLogging\Http\Tests;

use Garbetjie\RequestLogging\Http\LaravelServiceProvider;
use Garbetjie\RequestLogging\Http\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class LaravelServiceProviderTest extends TestCase
{
	private $handler;

	protected function getPackageProviders($app): array
	{
		return [LaravelServiceProvider::class];
	}

	protected function setUp(): void
	{
		$this->handler = new ArrayMonologHandler();

		parent::setUp();
	}


	protected function overrideApplicationBindings($app): array
	{
		return [
			Logger::class => function () {
				return new Logger(new \Monolog\Logger('monolog', [$this->handler]));
			}
		];
	}


	public function testLoggerIsSingleton()
	{
		$this->assertEquals($this->app->get(Logger::class), $this->app[Logger::class]);
		$this->assertEquals($this->app[Logger::class], $this->app->make(Logger::class));
	}

	public function testGuzzleClientInterfaceIsBound()
	{
		$this->assertTrue($this->app->has(ClientInterface::class));
	}

	public function testGuzzleClientIsBound()
	{
		$this->assertTrue($this->app->has(Client::class));
	}

	public function testGuzzleHandlerStackIsBound()
	{
		$this->assertTrue($this->app->has(HandlerStack::class));
	}

	public function testGuzzleMiddlewareIsInStack()
	{
		$stack = (string)$this->app->get(HandlerStack::class);

		$this->assertEquals(2, substr_count($stack, 'garbetjie/http-request-logger'));
	}

	public function testGuzzleMiddlewareIsInCustomStack()
	{
		$defaultStack = $this->app->get(HandlerStack::class);

		$this->app->bind(HandlerStack::class, function() {
			$stack = HandlerStack::create();
			$stack->push(
				function (callable $handler) {
					return function ($request, $options) use ($handler) {
						return $handler($request, $options);
					};
				},
				'custom middleware',
			);

			return $stack;
		});

		$customStack = $this->app->get(HandlerStack::class);

		// Ensure they're different.
		$this->assertNotEquals($customStack, $defaultStack);

		$this->assertEquals(2, substr_count($customStack, 'garbetjie/http-request-logger'));
	}

	public function testLaravelHttpClientRequestsAreLogged()
	{
		if (!class_exists(RequestSending::class)) {
			$this->markTestSkipped(RequestSending::class . ' does not exist in this version of Laravel.');
		}

		$this->assertCount(1, Event::getListeners(RequestSending::class));
		Http::get('https://example.org');

		$logs = $this->handler->logs();

		$this->assertCount(2, $logs);
		$this->assertEquals('http request sent', $logs[0]['message']);
	}

	public function testLaravelHttpClientResponsesAreLogged()
	{
		if (!class_exists(ResponseReceived::class)) {
			$this->markTestSkipped(ResponseReceived::class . ' does not exist in this version of Laravel.');
		}

		$this->assertCount(1, Event::getListeners(ResponseReceived::class));
		Http::get('https://example.org');

		$logs = $this->handler->logs();

		$this->assertCount(2, $logs);
		$this->assertEquals('http response received', $logs[1]['message']);
	}
}