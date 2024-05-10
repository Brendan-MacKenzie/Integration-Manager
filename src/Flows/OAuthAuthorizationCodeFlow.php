<?php

namespace BrendanMacKenzie\IntegrationManager\Flows;

use BrendanMacKenzie\IntegrationManager\Exceptions\OAuthException;
use BrendanMacKenzie\IntegrationManager\Models\Integration;
use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class OAuthAuthorizationCodeFlow implements AuthenticationInterface
{
    private $integration;
    private $apiClient;
    private $withState;

    public function __construct(
        Integration $integration, 
        ApiClient $apiClient,
        bool $withState = false,
    ) {
        $this->integration = $integration;
        $this->apiClient = $apiClient;
        $this->withState = $withState;
    }

    private function getRedirectUrl(int $id)
    {
        return config('app.url').'/integration/'.$id.'/authorization';
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
                    // Authorize access token
                    return $this->authorize();
                }
            }
        } else {
            return $this->authorize();
        }
    }

    public function getAccessToken()
    {
        if ($this->integration->authentication_endpoint) {
            throw new OAuthException("Authentication endpoint not provided.");
        }

        $code = $this->integration->getCredential('code');

        if (!$code) {
            throw new OAuthException("No authorization code set.");
        } 
        
        $body = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->integration->getCredential('client_id'),
            'client_secret' => $this->integration->getCredential('client_secret'),
            'redirect_uri' => $this->getRedirectUrl($this->integration->id),
        ];
        
        $customHeader = [
            'Content-Type' => 'x-www-form-urlencoded',
        ];

        try {
            $data = $this->apiClient->request('POST', $this->integration->authentication_endpoint, $body, $customHeader, false);

            if (!array_key_exists('access_token', $data)) {
                throw new OAuthException('Access token not received.');
            }

            $accessToken = $data['access_token'];
            $expiresIn = array_key_exists('expires_in', $data) ? $data['expires_in'] : null;

            $this->integration->addCredential('access_token', $accessToken);

            if ($expiresIn) {
                $this->integration->addCredential('expires_in', Carbon::now()->addSeconds($expiresIn)->toDateTimeString());
            }

            // Make sure you set the Authentication Headers in this function.
            $this->apiClient->setAuthenticationHeaders([
                'Authorization' => 'Bearer '.$this->integration->getCredential('access_token'),
            ]);
        } catch (Exception $exception) {
            $this->integration->removeCredential('state');
            $this->integration->removeCredential('code');
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

    private function authorize() 
    {
        $authorizationEndpoint = $this->integration->authorization_endpoint;
        $clientId = $this->integration->getCredential('client_id');
        $scope = $this->integration->getCredential('scope');
        $redirectUri = $this->getRedirectUrl($this->integration->id);
        $authorizationEndpoint = $authorizationEndpoint.'?response_type=code&client_id='.$clientId.'&redirect_uri='.$redirectUri;

        if ($this->withState) {
            $this->integration->addCredential('state', Str::random(16));
            $state = $this->integration->getCredential('state');
            $authorizationEndpoint = $authorizationEndpoint.'&state='.$state;
        }

        if ($scope) {
            $authorizationEndpoint = $authorizationEndpoint.'&scope='.$scope;
        }

        try {
            return $this->apiClient->request('GET', $authorizationEndpoint, [], [], false);
        } catch (Exception $exception) {
            $this->integration->removeCredential('state');
            $this->integration->removeCredential('code');
            $this->integration->removeCredential('access_token');
            $this->integration->removeCredential('expires_in');
            throw $exception;
        }
    }
}