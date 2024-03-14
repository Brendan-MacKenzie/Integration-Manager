<?php

namespace BrendanMacKenzie\IntegrationManager\Utils;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ApiClient
{
    /** @var Client */
    private $client;

    private $baseUrl;

    private $defaultHeaders;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            RequestOptions::TIMEOUT => 500,
            RequestOptions::HEADERS => $this->defaultHeaders,
        ]);
    }

    public function request(
        string $method, 
        string $endpoint, 
        array $body = [],
        array $headers = [],
    ) {
        try {
            $options = [];
            if (count($body) > 0) {
                $options[RequestOptions::JSON] = $body;
            }

            if (count($headers) > 0) {
                $options[RequestOptions::HEADERS] = $headers;
            }

            switch ($method) {
                case 'POST':
                    $response = $this->client->post(
                        $endpoint,
                        $options
                    );
                    break;
                case 'PUT':
                    $response = $this->client->put(
                        $endpoint,
                        $options
                    );
                    break;
                case 'DELETE':
                    $response = $this->client->delete(
                        $endpoint,
                        $options
                    );
                    break;
                case 'PATCH':
                    $response = $this->client->delete(
                        $endpoint,
                        $options
                    );
                    break;
                case 'GET':
                    $response = $this->client->get(
                        $endpoint,
                        $options
                    );
                    break;
            }
        } catch (Exception $exception) {
            report($exception);
            throw new Exception('Bad response on request in IntegrationService.');
        }

        return $this->verifyResponse($response); 
    }

    public function verifyResponse($response)
    {
        // TODO: support json, streams, files etc..
        
        $code = $response->getStatusCode();

        if ($code !== 200 && $code !== 202) {
            $contents = $response->getBody()->getContents();

            throw new Exception("Bad Integration Response: {$contents}");
        }

        return json_decode($response->getBody(), true);
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setDefaultHeaders(array $headers)
    {
        $this->defaultHeaders = $headers;
    }
}