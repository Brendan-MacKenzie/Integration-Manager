<?php

namespace BrendanMacKenzie\IntegrationManager\Utils;

use BrendanMacKenzie\IntegrationManager\Interfaces\IntegrationServiceInterface;
use BrendanMacKenzie\IntegrationManager\Models\Integration;

abstract class IntegrationService implements IntegrationServiceInterface
{
    /** @var ApiClient */
    private $apiClient;

    /** @var Integration */
    public $integration;

    public $defaultHeaders;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        $apiClient->setBaseUrl($this->integration->base_url);
        $apiClient->setAuthUrl($this->integration->auth_url);
        $apiClient->setDefaultHeaders($this->defaultHeaders);
    }

    public function getIntegrationName(): string
    {
        return $this->integration->option->name;
    }

    public function call(
        string $method, 
        string $endpoint, 
        array $body = [], 
        array $headers = [],
        bool $includeAuthentication = true,
        bool $useAuthUrl = false,
        bool $useFormParams = false
    ) {
        if ($includeAuthentication) {
            $authentication = $this->authenticate();
            if ($authentication) {
                return $authentication;
            }
        }

        return $this->apiClient->request($method, $endpoint, $body, $headers, $includeAuthentication, $useAuthUrl, $useFormParams);
    }
}