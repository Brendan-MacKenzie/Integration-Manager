<?php

namespace BrendanMacKenzie\IntegrationManager\Flows;

use BrendanMacKenzie\IntegrationManager\Exceptions\OAuthException;
use BrendanMacKenzie\IntegrationManager\Models\Integration;
use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
use Carbon\Carbon;
use Exception;

class OAuthClientCredentialFlow implements AuthenticationInterface
{
    private $integration;
    private $apiClient;
    private $useFormParams;

    public function __construct(
        Integration $integration, 
        ApiClient $apiClient,
        bool $useFormParams = true
    ) {
        $this->integration = $integration;
        $this->apiClient = $apiClient;
        $this->useFormParams = $useFormParams;
    }

    public function authenticate()
    {
        // Check if integration has an access token
        if ($this->integration->getCredential('access_token')) {
            // Check if access token is expired.
            $expiresIn = $this->integration->getCredential('expires_in');
            if ($expiresIn) {
                $expiresIn = Carbon::parse($expiresIn);

                if (Carbon::parse($expiresIn)->lte(Carbon::now()->addWeek())) {
                    // Renew access token
                    $this->integration->removeCredential('access_token');
                    $this->authenticate();
                }
            }
        } else {
            $this->getAccessToken();
        }

        $this->apiClient->setAuthenticationHeaders([
            'Authorization' => 'Bearer '.$this->integration->getCredential('access_token'),
        ]);
    }

    public function getAccessToken()
    {
        if (!$this->integration->authentication_endpoint) {
            throw new OAuthException("Authentication endpoint not provided.");
        }
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->integration->getCredential('client_id'),
            'client_secret' => $this->integration->getCredential('client_secret'),
        ];

        $scope = $this->integration->getCredential('scope');

        if ($scope) {
            $body['scope'] = $scope;
        }

        try {
            $data = $this->apiClient->request('POST', $this->integration->authentication_endpoint, $body, [], false, true, $this->useFormParams);

            if (!array_key_exists('access_token', $data)) {
                throw new OAuthException('Access token not received.');
            }

            $accessToken = $data['access_token'];
            $expiresIn = array_key_exists('expires_in', $data) ? $data['expires_in'] : null;

            $this->integration->addCredential('access_token', $accessToken);
            $this->integration->addCredential('expires_in', Carbon::now()->addSeconds($expiresIn)->toDateTimeString());
        } catch (Exception $exception) {
            $this->integration->removeCredential('access_token');
            $this->integration->removeCredential('expires_in');
            throw $exception;
        }
    }

    public function getAuthenticationHeaders()
    {
        return [
            'Connection' => 'Keep-Alive',
            'Authorization' => 'Bearer '.$this->integration->getCredential('access_token')
        ];
    }
}
