<?php

namespace BrendanMacKenzie\IntegrationManager\Http\Controllers;

use BrendanMacKenzie\IntegrationManager\Exceptions\OAuthException;
use BrendanMacKenzie\IntegrationManager\Flows\OAuthAuthorizationCodeFlow;
use BrendanMacKenzie\IntegrationManager\Models\Integration;
use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;

class AuthorizationController extends Controller
{
    public function authorize(Request $request, $id)
    {
        $integration = Integration::find($id);

        if (!$integration) {
            throw new OAuthException("Could not find integration", 500);
        }

        if (!$request->has('code')) {
            throw new OAuthException("Authorization of integration " . $integration->id . " went wrong. No code is provided.", 500);
        }

        // Verify request with state parameter if present. Check integration for state credential before processing request.
        if ($integration->getCredential('state')) {
            if ($request->has('state') && $request->input('state')) {
                if ($request->input('state') == $integration->getCredential('state')) {
                    // Set authorization code in credentials.
                    $integration->setCredential('code', $request->input('code'));
                } else {
                    throw new OAuthException("Redirect contains invalid state.", 500);
                }
            } else{
                throw new OAuthException("Integration requires state.", 500);
            }
        }

        // Get access token
        $apiClient = new ApiClient();
        $apiClient->setBaseUrl($integration->base_url);
        $oAuthAuthorizationFlow = new OAuthAuthorizationCodeFlow($integration, $apiClient);
        $oAuthAuthorizationFlow->getAccessToken($integration);

        return response('Authorized.', 200);
    }
}