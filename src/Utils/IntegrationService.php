<?php

namespace BrendanMacKenzie\IntegrationManager\Utils;

use BrendanMacKenzie\IntegrationManager\Interfaces\IntegrationServiceInterface;
use BrendanMacKenzie\IntegrationManager\Models\Integration;

abstract class IntegrationService implements IntegrationServiceInterface
{
    /** @var ApiClient */
    private $apiClient;

    public $baseUrl;
    public $defaultHeaders;

    /** @var Integration */
    public $integration;

    protected $credentials;

    public function __construct(ApiClient $apiClient)
    {
        $this->credentials = $this->decrypt();
        $this->apiClient = $apiClient;
        $this->apiClient->setBaseUrl($this->baseUrl);
        $this->apiClient->setDefaultHeaders($this->defaultHeaders);
    }

    public function getIntegrationName(): string
    {
        return $this->integration->option->name;
    }

    public function setCredentials(array $credentials): void
    {
        $encrypted = $this->encrypt($credentials);
        $this->integration->credentials = $encrypted;
        $this->integration->save();
        $this->integration->refresh();
    }

    public function addCredential(string $key, string $value)
    {
        $credentials = $this->decrypt();
        $credentials[$key] = $value;
        $this->setCredentials($credentials);
    }

    public function removeCredential(string $key)
    {
        $credentials = $this->decrypt();
        unset($credentials[$key]);
        $this->setCredentials($credentials);
    }

    public function getCredential(string $key)
    {
        $credentials = $this->decrypt();
        if (array_key_exists($key, $credentials)) {
            return $credentials[$key];
        }

        return;
    }

    public function call(
        string $method, 
        string $endpoint, 
        array $body = [], 
        array $headers = []
    ) {
        $this->authenticate();
        return $this->apiClient->request($method, $endpoint, $body, $headers);
    }

    protected function encrypt(array $credentials) : array
    {
        $encrypted = [];
        foreach ($credentials as $key => $value) {
            $encrypted[$key] = encrypt($value);
        }

        return $encrypted;
    }

    protected function decrypt() : array
    {
        $credentials = json_decode($this->integration->credentials, true);
        
        if (!$credentials) {
            return;
        }

        $decrypted = [];
        foreach ($credentials as $key => $value) {
            $decrypted[$key] = decrypt($value);
        }

        return $decrypted;
    }
}