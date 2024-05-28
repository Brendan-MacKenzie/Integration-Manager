<?php

namespace BrendanMacKenzie\IntegrationManager\Flows;

use BrendanMacKenzie\IntegrationManager\Exceptions\OAuthException;
use BrendanMacKenzie\IntegrationManager\Models\Integration;
use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class OAuthAuthorizationCodeFlow implements AuthenticationInterface
{
    private $integration;
    private $apiClient;
    private $withState;
    private $useFormParams;
    private $redirectUrl;

    public function __construct(
        Integration $integration, 
        ApiClient $apiClient,
        bool $withState = false,
        bool $useFormParams = false,
        ?string $redirectUrl = null
    ) {
        $this->integration = $integration;
        $this->apiClient = $apiClient;
        $this->withState = $withState;
        $this->useFormParams = $useFormParams;
        $this->redirectUrl = ($redirectUrl) ? $redirectUrl : config('app.url').config('integrations.redirect_uri');
    }

    private function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    public function authenticate()
    {
        // Check if integration has an access token
        if ($this->integration->getCredential('access_token')) {
            // Check if access token is expired.
            $expiresIn = $this->integration->getCredential('expires_in');
            if ($expiresIn) {
                $expiresIn = Carbon::parse($expiresIn);

                if ($expiresIn->lte(Carbon::now())) {
                    try {
                        $this->getAccessToken();
                    } catch (Exception $exception) {
                        report($exception);
                        return $this->authorize();
                    }
                }
            }

            // Set access token
            $this->apiClient->setAuthenticationHeaders([
                'Authorization' => 'Bearer '.$this->integration->getCredential('access_token'),
            ]);
        } else {
            // Get new authorization code
            return $this->authorize();
        }
    }

    public function getAccessToken()
    {
        if (!$this->integration->authentication_endpoint) {
            throw new OAuthException("Authentication endpoint not provided.");
        }

        $code = $this->integration->getCredential('code');

        if (
            !$this->integration->getCredential('access_token') && 
            !$code
        ) {
            throw new OAuthException("No authorization code set.");
        } 
        
        $refreshToken = $this->integration->getCredential('refresh_token');

        if ($refreshToken) {
            $body = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->integration->getCredential('client_id'),
                'client_secret' => $this->integration->getCredential('client_secret'),
            ];
        } else {
            $body = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->integration->getCredential('client_id'),
                'client_secret' => $this->integration->getCredential('client_secret'),
                'redirect_uri' => $this->getRedirectUrl(),
            ];
        }

        try {
            $data = $this->apiClient->request('POST', $this->integration->authentication_endpoint, $body, [], false, true, $this->useFormParams);

            if (!is_array($data) || !array_key_exists('access_token', $data)) {
                throw new OAuthException('Access token not received.');
            }

            $accessToken = $data['access_token'];
            $expiresIn = array_key_exists('expires_in', $data) ? (int)$data['expires_in'] : null;
            $refreshToken = array_key_exists('refresh_token', $data) ? $data['refresh_token'] : null;

            $this->integration->addCredential('access_token', $accessToken);

            if ($expiresIn) {
                $this->integration->addCredential('expires_in', Carbon::now()->addSeconds($expiresIn)->toDateTimeString());
            }

            if ($refreshToken) {
                $this->integration->addCredential('refresh_token', $refreshToken);
            }
            
        } catch (Exception $exception) {
            $this->integration->removeCredential('state');
            $this->integration->removeCredential('code');
            $this->integration->removeCredential('access_token');
            $this->integration->removeCredential('refresh_token');
            $this->integration->removeCredential('expires_in');
            throw $exception;
        }
    }

    public function processRedirect(Request $request)
    {
        if (!$request->has('code')) {
            throw new OAuthException("Authorization of integration " . $this->integration->id . " went wrong. No code is provided.", 500);
        }

        // Verify request with state parameter if present. Check integration for state credential before processing request.
        if ($this->integration->getCredential('state')) {
            if ($request->has('state') && $request->input('state')) {
                if ($request->input('state') == $this->integration->getCredential('state')) {
                    // Set authorization code in credentials.
                    $this->integration->addCredential('code', $request->input('code'));
                } else {
                    $this->integration->removeCredential('state');
                    $this->integration->removeCredential('code');
                    $this->integration->removeCredential('access_token');
                    $this->integration->removeCredential('refresh_token');
                    $this->integration->removeCredential('expires_in');
                    throw new OAuthException("Redirect contains invalid state.", 500);
                }
            } else {
                $this->integration->removeCredential('state');
                $this->integration->removeCredential('code');
                $this->integration->removeCredential('access_token');
                $this->integration->removeCredential('refresh_token');
                $this->integration->removeCredential('expires_in');
                throw new OAuthException("Integration requires state.", 500);
            }
        } else {
            // Set authorization code in credentials.
            $this->integration->addCredential('code', $request->input('code'));
        }

        // Get access token
        $this->apiClient->setBaseUrl($this->integration->base_url);
        $this->apiClient->setAuthUrl($this->integration->auth_url);
        $this->getAccessToken();
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
        $authorizationEndpoint = $this->apiClient->getAuthUrl().$this->integration->authorization_endpoint;
        $clientId = $this->integration->getCredential('client_id');
        $scope = $this->integration->getCredential('scope');
        $redirectUrl = $this->getRedirectUrl();
        $authorizationEndpoint = $authorizationEndpoint.'?response_type=code&client_id='.$clientId.'&redirect_uri='.$redirectUrl;

        if ($this->withState) {
            $this->integration->addCredential('state', Str::random(16));
            $state = $this->integration->getCredential('state');
            $authorizationEndpoint = $authorizationEndpoint.'&state='.$state;
        }

        if ($scope) {
            $authorizationEndpoint = $authorizationEndpoint.'&scope='.$scope;
        }

        return $authorizationEndpoint;
    }
}