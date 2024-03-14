<?php

namespace BrendanMacKenzie\IntegrationManager\Utils;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ApiClient
{
    private $baseUrl;

    private $defaultHeaders = array();

    private $authenticationHeaders = array();

    public function request(
        string $method, 
        string $endpoint, 
        array $body = [],
        array $customHeaders = [],
        bool $includeAuthenticationHeaders = true
    ) {
        try {
            $client = new Client([
                'base_uri' => $this->getBaseUrl(),
                RequestOptions::TIMEOUT => 500,
            ]);

            $options = [];
            if (count($body) > 0) {
                $options[RequestOptions::JSON] = $body;
            }

            $allHeaders = $this->getDefaultHeaders();

            if ($includeAuthenticationHeaders) {
                $allHeaders = array_merge($allHeaders, $this->getAuthenticationHeaders());
            }

            if (count($customHeaders) > 0) {
                $allHeaders = array_merge($allHeaders, $customHeaders);
            }

            $options[RequestOptions::HEADERS] = $allHeaders;

            switch ($method) {
                case 'POST':
                    $response = $client->post(
                        $endpoint,
                        $options
                    );
                    break;
                case 'PUT':
                    $response = $client->put(
                        $endpoint,
                        $options
                    );
                    break;
                case 'DELETE':
                    $response = $client->delete(
                        $endpoint,
                        $options
                    );
                    break;
                case 'PATCH':
                    $response = $client->delete(
                        $endpoint,
                        $options
                    );
                    break;
                case 'GET':
                    $response = $client->get(
                        $endpoint,
                        $options
                    );
                    break;
            }
        } catch (Exception $exception) {
            report($exception);
            throw new Exception('Bad response on request in IntegrationService. Message: '. $exception->getMessage());
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

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setDefaultHeaders(array $headers)
    {
        $this->defaultHeaders = $headers;
    }

    public function getDefaultHeaders()
    {
        return $this->defaultHeaders;
    }

    public function setAuthenticationHeaders(array $headers)
    {
        $this->authenticationHeaders = $headers;
    }

    public function getAuthenticationHeaders()
    {
        return $this->authenticationHeaders;
    }
}