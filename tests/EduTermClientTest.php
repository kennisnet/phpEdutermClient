<?php
namespace Test\Kennisnet\EdutermClient;

use Kennisnet\EdutermClient\EdutermClient;
use PHPUnit\Framework\TestCase;

class EduTermClientTest extends TestCase {

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Provided API key is not a valid uuid:
     */
    public function testInvalidUUID()
    {
        new EdutermClient('');
    }

    public function testSetQuery()
    {
        $apiKey = 'c001f86a-4f8f-4420-bd78-381c615ecedc';

        $expectedQuery = sprintf(
            'http://api.onderwijsbegrippen.kennisnet.nl/1.0/Query/test?api_key=%s&foo=bar',
            $apiKey
        );

        $eduTermClient = new EdutermClient($apiKey);

        $eduTermClient->setQuery('test', [
            'foo' => 'bar'
        ]);

        $this->assertEquals($expectedQuery, $eduTermClient->query);
    }

    public function testSetBaseUrl()
    {
        $apiKey = 'c001f86a-4f8f-4420-bd78-381c615ecedc';
        $baseUrl = 'http://www.kn.nu/';
        $eduTermClient = new EdutermClient($apiKey);

        // Volgorde is belangrijk hier!
        $eduTermClient->setBaseUrl($baseUrl);
        $eduTermClient->setQuery('test');

        $this->assertEquals('http://www.kn.nu/test?api_key=c001f86a-4f8f-4420-bd78-381c615ecedc', $eduTermClient->query);
    }

    public function testEndpoint()
    {
        $apiKey = 'c001f86a-4f8f-4420-bd78-381c615ecedc';
        $expectedQuery = sprintf(
            'http://api.onderwijsbegrippen.kennisnet.nl/1.0/Query/test?api_key=%s&endpoint=testendpoint',
            $apiKey
        );

        $eduTermClient = new EdutermClient($apiKey);

        // Volgorde is belangrijk hier!
        $eduTermClient->setEndpoint('testendpoint');
        $eduTermClient->setQuery('test');

        $this->assertEquals($expectedQuery, $eduTermClient->query);
    }

}
