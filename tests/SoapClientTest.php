<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\SoapClient;
use GuzzleHttp\Client;
use Monolog\Test\TestCase;
use \SoapClient as BaseSoapClient;

class SoapClientTest extends TestCase
{
    public function testCreateNewInstanceWithOptions()
    {
        $client = new SoapClient(new Client(), null, ['location' => '/', 'uri' => 'http://example.org']);

        $this->assertInstanceOf(SoapClient::class, $client);
        $this->assertInstanceOf(BaseSoapClient::class, $client);
    }

    public function testCreateNewInstanceWithoutOptions()
    {
        $client = new SoapClient(new Client(), __DIR__ . '/wsdl.xml');

        $this->assertInstanceOf(SoapClient::class, $client);
        $this->assertInstanceOf(BaseSoapClient::class, $client);
    }
}
