<?php

namespace Garbetjie\RequestLogging\Http\Tests;

use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\SoapServer;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use \SoapServer as BaseSoapServer;
use Throwable;
use XMLReader;
use function base64_decode;
use function file_put_contents;
use function libxml_use_internal_errors;
use function ob_end_clean;
use function print_r;

class ASoapServerTest extends TestCase
{
    /**
     * @var ArrayMonologHandler
     */
    protected $handler;

    /**
     * @var Logger
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();
        $this->logger = new Logger(new Monolog('test', [$this->handler]));
    }

	/**
	 * @runInSeparateProcess
	 */
	public function testLogMessagesAreLogged()
	{
		$server = new SoapServer($this->logger, __DIR__ . '/wsdl.xml');

		$server->setObject(new class {
			public function GetBook() {
				return [
					'ID' => '1',
					'Title' => 'Book',
					'Author' => 'Author',
				];
			}
		});

		ob_start();
		$server->handle(<<<SOAP
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:book="http://www.cleverbuilder.com/BookService/">
               <soapenv:Header/>
               <soapenv:Body>
                  <book:GetBook>
                     <ID>1</ID>
                  </book:GetBook>
               </soapenv:Body>
            </soapenv:Envelope>
        SOAP);
		ob_end_clean();

		$this->assertCount(2, $this->handler->logs());
	}

    public function testCreateNewInstanceWithoutOptions()
    {
        $server = new SoapServer($this->logger, __DIR__ . '/wsdl.xml');

        $this->assertInstanceOf(BaseSoapServer::class, $server);
    }

    public function testCreateNewInstanceWithOptions()
    {
        $server = new SoapServer($this->logger, null, ['location' => '/', 'uri' => 'https://example.org']);

        $this->assertInstanceOf(BaseSoapServer::class, $server);
    }
}
