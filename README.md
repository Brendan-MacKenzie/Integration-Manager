# Brendan&MacKenzie - Integration Manager

Brendan&MacKenzie's Integration Manager is a package to easily connect API's to your Laravel project with a predefined logic structure and additional tools.

## Requirements
* PHP 7.3 or higher
* guzzlehttp/guzzle ^7.5


## Installation ###
1. Run `composer require brendan-mackenzie/integration-manager` inside your Laravel project.
2. Run `php artisan vendor:publish --tag=integration-config` to publish the config file.
3. Don't forget to add the names of the integrations you want to implement inside the `options` array in your `integrations.php`config file.
4. Run `php artisan vendor:publish --tag=integration-migrations` to publish the migration file.
5. Run `php artisan migrate` to migrate the integration tables. Your options in the config file will be seeded aswell.

## Demo ##
If you want to see examples in action, please install [our Laravel demo project](https://github.com/Brendan-MacKenzie/Integration-Manager-Demo) and take a look at the code!

## You have 3 options to connect to an API with the Integration Manager
- Option 1: With the custom authentication flow.
- Option 2: With the OAuth Authorization Grant flow.
- Option 3: With the OAuth Client Credential Grant flow.

## Option 1: Define a custom integration for a project model ##
To link an integration and it's credentials to a model inside your own project, simply create an Integration model, link it to your model (polyformic relation), make sure you link the right IntegrationOption and you're done!

```
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Models\IntegrationOption;

    // Find the right integration.
    $integrationOption = IntegrationOption::where('name', 'WhatsApp')->first();

    // Create an integration rule for your model.
    $integration = Integration::create([
        'integration_option_id' => $integrationOption->id,
        'base_url' => 'https://api.whatsapp.com/v1/',
        'auth_url' => 'https://api.whatsapp.com/v1/',
        'authorization_endpoint' => null,
        'authentication_endpoint' => null,
    ]);

    // Associate your model to the integration.
    $integration->owner()->associate($yourModel);

    // Store the credentials for the integration with your own integration service (like the example down below: WhatsAppService.php):
    $credentials = [
        'API_ID' => 'xxxx1234',
        'API_KEY' => '4321aaaa',
    ];

    $integration->setCredentials($credentials);
    
```

## Option 1: Building a custom integration service ##
To implement an API integration in your Laravel project you can extend the abstract class `BrendanMacKenzie\IntegrationManager\Utils\IntegrationService.php` inside your own integration service class.

For example if you want to build an integration with the WhatsApp API, you can create your own `WhatsAppService.php` class like this:

```
    <?php

    namespace App\Services;

    use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Utils\IntegrationService;

    class WhatsAppService extends IntegrationService
    {
        /** @var Integration */
        private $integration;

        private $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        /** @var ApiClient */
        private $apiClient;

        public function __construct(Integration $integration)
        {
            $this->integration = $integration;
            $this->apiClient = new ApiClient();
            $apiClient->setBaseUrl($this->integration->base_url);
            $apiClient->setDefaultHeaders($this->defaultHeaders);
            parent::__construct($apiClient);
        }

        public function authenticate(): void
        {
            // Write your authentication logic here.
            // We predefined the OAuth Authorization Code Grant, OAuth Client Credential Grant and a API Key flow. You can use them in this method, just like the examples below.
            // The package is checking this function everytime it is making a request to the API.
            // Make sure you write it with that in mind if you're building a custom authentication flow here.
            
            // Make sure you set the Authentication Headers in this function.
            $this->apiClient->setAuthenticationHeaders([
                'Authorization' => 'Token 1234abc',
            ]);
        }

        // Example of a request on the WhatsApp API build with Integration Manager:

        public function getChats()
        {
            // Build a body for the request with a simple array.
            $body = [
                'user_id' => 1234,
            ];

            $headers = [
                // Add custom headers if needed for the request.
                'X-Custom-Header' => 'test'
            ];

            // Build all requests with one simple method..
            $response = $this->call('GET', '/chats', $body, $headers);

            // If you want to make a request which does not need authentication headers, use:
            $response = $this->call('GET', '/chats', $body, $headers, false);

            return $response->data;
        }

    }
```

## Option 2: Define an OAuth Authorization flow integration for a project model ##
To create an integration with the standard OAuth Authorization flow and link it's credentials to a model inside your own project, start by doing the following...

```
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Models\IntegrationOption;

    // Find the right integration.
    $integrationOption = IntegrationOption::where('name', 'Exact Online')->first();

    // Create an integration rule for your model.
    $integration = Integration::create([
        'integration_option_id' => $integrationOption->id,
        'base_url' => 'https://start.exactonline.nl/api/',
        'auth_url' => 'https://start.exactonline.nl/api/',
        'authorization_endpoint' => '/oauth2/auth',
        'authentication_endpoint' => '/oauth2/token',
    ]);

    // Associate your model to the integration.
    $integration->owner()->associate($yourModel);

    // Store the credentials for the integration with your own integration service (like the example down below: ExactOnlineService.php):
    $credentials = [
        'client_id' => 'xxxx1234',
        'client_secret' => 'xxxx1234',
    ];

    $integration->setCredentials($credentials);
    
```
## Option 2: Building a OAuth Authorization integration service ##
To implement an API integration in your Laravel project with the OAuth Authorization flow, you can extend the abstract class `BrendanMacKenzie\IntegrationManager\Utils\IntegrationService.php` inside your own integration service class.

For example if you want to build an integration with the Exact Online API, you can create your own `ExactOnlineService.php` class like this:

```
    <?php

    namespace App\Services;

    use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Utils\IntegrationService;
    use BrendanMacKenzie\IntegrationManager\Flows\OAuthAuthorizationCodeFlow;

    class ExactOnlineService extends IntegrationService
    {
        /** @var Integration */
        private $integration;

        private $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        /** @var ApiClient */
        private $apiClient;

        public function __construct(
            Integration $integration
        ) {
            $this->integration = $integration;
            $this->apiClient = new ApiClient();
            parent::__construct($this->apiClient);
        }

        public function authenticate(): void
        {
            // We predefined the OAuth Authorization Code Grant. You can use this in this method.
            // The package is checking this function everytime it is making a request to the API.

            $oAuthAuthorizationFlow = new OAuthAuthorizationCodeFlow(
                $this->integration,
                $this->apiClient,
                false,              // You can determine if you want to work with a state value to prevent yourself from CRSF attacks. Set true if you want to enable prevention.
                true                // Sometimes a Authorization Grant flow for an API requires you to make the authorization request with a x-www-form-urlencoded encoded body. You can turn this one with the $useFormParams parameter.
            );

            // This function returns a authorization redirect URL for the user if the Exact Online user has not granted your application yet.
            $authorizationUrl = $oAuthAuthorizationFlow->authenticate();
            if ($authorizationUrl) {
                return $authorizationUrl;
            }
        }

        // Example of a request on the Exact Online API build with Integration Manager:

        public function getMe()
        {
            // Build a body for the request with a simple array.
            $body = [
                'company_id' => 1234,
            ];

            $headers = [
                // Add custom headers if needed for the request.
                'X-Custom-Header' => 'test'
            ];

            // Build all requests with one simple method..
            $response = $this->call('GET', '/v1/current/Me', $body, $headers);

            // If you want to make a request which does not need authentication headers, use:
            $response = $this->call('GET', '/v1/current/Me', $body, $headers, false);

            return $response->data;
        }

    }
```

## Option 2: Creating the redirect URL for the external application to redirect to ##
When you're implementing the OAuth Authorization Grant flow for the API you're about to use, it is necessary to implement a redirect route for the API to redirect to. This route will set the authorization code in our flow and immediately request an access token for the API.

After creating the route "/integration/callback" in your web.php.
If you want a custom route for your redirect route, please change the "redirect_uri" in your `integrations.php` config file.
In the controller of your choice you implement this piece of code and link it to your new route:

```
<?php

namespace App\Http\Controllers;

use BrendanMacKenzie\IntegrationManager\Flows\OAuthAuthorizationCodeFlow;
use BrendanMacKenzie\IntegrationManager\Models\Integration;
use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function authorizationRedirect(Request $request)
    {
        // Optional: Validate the incoming request before processing it to the package.
        $request->validate([
            'code' => 'required|string',
        ]);

        // Get the right integration for the request, usually you can get the integration via the Authenticated user.
        $integration = Auth::user()->model->integrations->first();
        
        // Build the API Client and OAuth Authorization flow for the selected integration.
        $apiClient = new ApiClient();
        $apiClient->setBaseUrl($integration->base_url);
        $apiClient->setAuthUrl($integration->auth_url);

        $oAuthAuthorizationFlow = new OAuthAuthorizationCodeFlow(
            $integration,
            $apiClient,
            false,
            true
        );

        // Process the redirected request with the provided authorization code.
        try {
            $oAuthAuthorizationFlow->processRedirect($request);
        } catch (Exception $exception) {
            throw $exception;
        }

        // Redirect to any page you like.
        return redirect('/authorization');
    } 
}
```

## Option 3: Define an OAuth Client Credential flow integration for a project model ##
To create an integration with the standard OAuth Client Credential flow and link it's credentials to a model inside your own project, start by doing the following...

```
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Models\IntegrationOption;

    // Find the right integration.
    $integrationOption = IntegrationOption::where('name', 'Mr. Einstein')->first();

    // Create an integration rule for your model.
    $integration = Integration::create([
        'integration_option_id' => $integrationOption->id,
        'base_url' => 'https://www.einstein.brenzie.nl/',
        'authentication_endpoint' => '/oauth/token',
    ]);

    // Associate your model to the integration.
    $integration->owner()->associate($yourModel);

    // Store the credentials for the integration with your own integration service (like the example down below: MrEinsteinService.php):
    $credentials = [
        // Required with Client Credential flow:
        'client_id' => 'xxxx1234',
        'client_secret' => 'xxxx1234',
    ];

    $integration->setCredentials($credentials);
    
```
## Option 3: Building a OAuth Client Credential integration service ##
To implement an API integration in your Laravel project with the OAuth Client Credential flow, you can extend the abstract class `BrendanMacKenzie\IntegrationManager\Utils\IntegrationService.php` inside your own integration service class.

For example if you want to build an integration with the Mr. Einstein API, you can create your own `MrEinsteinService.php` class like this:

```
    <?php

    namespace App\Services;

    use BrendanMacKenzie\IntegrationManager\Utils\ApiClient;
    use BrendanMacKenzie\IntegrationManager\Models\Integration;
    use BrendanMacKenzie\IntegrationManager\Utils\IntegrationService;
    use BrendanMacKenzie\IntegrationManager\Flows\OAuthClientCredentialFlow;

    class MrEinsteinService extends IntegrationService
    {
        /** @var Integration */
        private $integration;

        private $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        /** @var ApiClient */
        private $apiClient;

        public function __construct(
            Integration $integration
        ) {
            $this->integration = $integration;
            $this->apiClient = new ApiClient();
            parent::__construct($this->apiClient);
        }

        public function authenticate(): void
        {
            // We predefined the OAuth Authorization Code Grant. You can use this in this method.
            // The package is checking this function everytime it is making a request to the API.

            $oAuthClientCredentialFlow = new OAuthClientCredentialFlow(
                $this->integration,
                $this->apiClient
            );

            $oAuthClientCredentialFlow->authenticate();
        }

        // Example of a request on the LinkedIn API build with Integration Manager:

        public function getClientInfo()
        {
            // Build a body for the request with a simple array.
            $body = [
                'company_id' => 1234,
            ];

            $headers = [
                // Add custom headers if needed for the request.
                'X-Custom-Header' => 'test'
            ];

            // Build all requests with one simple method..
            $response = $this->call('GET', '/client/app/'.$this->integration->getCredential('client_id'), $body, $headers);

            // If you want to make a request which does not need authentication headers, use:
            $response = $this->call('GET', '/client/app/'.$this->integration->getCredential('client_id'), $body, $headers, false);

            return $response->data;
        }

    }
```

## Available methods on IntegrationService ##

| Method    | Description |
| -------- | ------- |
| getIntegrationName()  | Returns the name of the integration    |
| authenticate()  | Runs the logic to authenticate for the API endpoints.    |
| call(string $method, string $endpoint, array $body = [], array $headers = []) | Make a request to the API of the integration. Return the response in the right format.    |

## Available methods on the Integration model ##

| Method    | Description |
| -------- | ------- |
| setCredentials(array $credentials)  | Set a new array with credentials. This method overrides the already existing credentials set.    |
| addCredential(string $key, string $value)  | Add a credential to the set of credentials. If the credential already exists, it replaces it.    |
| removeCredential(string $key)  | Remove a credential attribute from the set of credentials.   |
| getCredential(string $key)  | Returns the decrypted value of a credential attribute.    |

## Available methods on the OAuthAuthorizationCodeFlow class ##

| Method    | Description |
| -------- | ------- |
| authenticate()  | Runs the default standard logic to authenticate with the OAuth 2.0 Authorization Grant flow.    |
| getAccessToken()  | Obtains an access token from the API with the logic and data available from the integration. It uses the authorization code to obtaine an access token, when a refresh token is available it will obtain a new access token with the refresh token.    |
| processRedirect(Request $request)  | Includes logic to handle a request redirected from the API after authorizing the application. |
| getAuthenticationHeaders()  | Return the authentication headers needed for an API request. The array contains a header with the access token. |

## Available methods on the OAuthClientCredentialFlow class ##

| Method    | Description |
| -------- | ------- |
| authenticate()  | Runs the default standard logic to authenticate with the OAuth 2.0 Client Credential Grant flow.    |
| getAccessToken()  | Obtains an access token from the API with the logic and data available from the integration.    |
| getAuthenticationHeaders()  | Return the authentication headers needed for an API request. The array contains a header with the access token. |

## Acknowledgements ##
 - [Brendan&MacKenzie](https://www.brendan-mackenzie.com)

 ## Authors

- [@wouterdeberg](https://github.com/wouterdeberg)
- [@Brendan-MacKenzie](https://github.com/Brendan-MacKenzie)

## Issues
- [Report an issue here](https://github.com/Brendan-MacKenzie/Integration-Manager/issues/new)
- [List of open issues](https://github.com/Brendan-MacKenzie/Integration-Manager/issues)
