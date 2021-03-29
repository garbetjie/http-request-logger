<?php

namespace Garbetjie\Http\RequestLogging;

use function ob_get_flush;
use function ob_start;

class SoapServer extends \SoapServer
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger, $wsdl, array $options = [])
    {
        $this->logger = $logger;

        parent::__construct($wsdl, $options);
    }

    public function handle($request = null)
    {
        // Log the request.
        $entry = $this->logger->request($request, Logger::DIRECTION_IN);

        // Start buffering.
        ob_start();

        // Handle the request.
        parent::handle($request);

        // Get the response.
        $body = ob_get_flush();

        $this->logger->response($entry, $body);
    }
}
