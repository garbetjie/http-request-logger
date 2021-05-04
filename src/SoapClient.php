<?php

namespace Garbetjie\RequestLogging\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use SimpleXMLElement;
use SoapFault;
use function array_filter;
use function json_decode;
use function json_encode;
use function key;
use function stripos;

class SoapClient extends \SoapClient
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     * @param string|null $wsdl
     * @param array|null $options
     * @throws \SoapFault
     */
    public function __construct(ClientInterface $client, $wsdl = null, array $options = null)
    {
        $this->client = $client;

        parent::__construct($wsdl, $options ?: []);
    }



    /**
     * Override the original __doRequest method, and make the call using Guzzle.
     *
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     *
     * @return string
     * @throws SoapFault
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        try {
            $response = $this->client->send(
                new Request(
                    'POST',
                    $location,
                    ['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => $action],
                    $request
                )
            );

            return $response->getBody()->getContents();
        } catch (BadResponseException $e) {
            throw $this->createSoapFault($e->getResponse()->getBody()->getContents());
        } catch (ConnectException $e) {
            throw new SoapFault('HTTP', "Timeout encountered connecting to SOAP endpoint '{$location}'.");
        } catch (GuzzleException $e) {
            throw new SoapFault('HTTP', "Unexpected Guzzle exception encountered sending SOAP request to '{$location}'");
        } catch (\Throwable $e) {
            throw new SoapFault('HTTP', "Unexpected exception encountered sending SOAP request to '{$location}'");
        }
    }

    /**
     * Receives an XML response body, and constructs a \SoapFault instance from it. This SOAPFault structure is
     * a standardised structure.
     *
     * See https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383507 for documentation on the SOAPFault
     * structure.
     *
     * @param string $xml
     * @return SoapFault
     */
    private function createSoapFault(string $xml): SoapFault
    {
        // Create the XML doc.
        $doc = new SimpleXMLElement($xml);

        // Extract the SOAP namespace used.
        $ns = key(
            array_filter(
                $doc->getNamespaces(),
                function ($url) {
                    return stripos($url, 'schemas.xmlsoap.org/soap/envelope') !== false;
                }
            )
        );

        $faultElement = $doc->xpath("//{$ns}:Body/{$ns}:Fault");
        $faultElement = $faultElement ? $faultElement[0] : null;

        if (!$faultElement) {
            return new SoapFault('WSDL', 'Unable to extract `Fault` element from SOAP response.');
        }

        $faultName = $faultElement['name'] ? (string)$faultElement['name'] : null;

        $faultCode = $faultElement->xpath('.//faultcode');
        $faultCode = $faultCode ? (string)$faultCode[0] : null;

        $faultString = $doc->xpath('.//faultstring');
        $faultString = $faultString ? (string)$faultString[0] : null;

        $faultActor = $doc->xpath('.//faultactor');
        $faultActor = $faultActor ? (string)$faultActor[0] : null;

        $detail = $doc->xpath('.//detail');
        $detail = $detail ? json_decode(json_encode($detail[0])) : null;

        return new SoapFault($faultCode, $faultString, $faultActor, $detail, $faultName);
    }
}
