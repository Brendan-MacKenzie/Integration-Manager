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

## Define an integration for a project model ##
To link an integration and it's credentials to a model inside your own project, simply create an Integration model, link it to your model (polyformic relation), make sure you link the right IntegrationOption and you're done!

```
    use BrendanMacKenzie\IntegrationManager\Models\Integration;

    // Find the right integration.
    $integrationOption = IntegrationOption::where('name', 'WhatsApp')->first();

    // Create an integration rule for your model.
    $integration = Integration::create([
        'integration_option_id' => $integrationOption->id,
        'base_url' => 'https://api.whatsapp.com/v1',
        'authorization_endpoint' => '/oauth/code',
        'authentication_endpoint' => '/oauth/token',
    ]);

    // Associate your model to the integration.
    $integration->owner()->associate($yourModel);

    // Store the credentials for the integration with your own integration service (like the example down below: WhatsAppService.php):
    $credentials = [
        'API_ID' => 'xxxx1234',
        'API_KEY' => '4321aaaa',
        'user_id' => 1234,
    ];

    $integration->setCredentials($credentials);
    
```

## Building an integration ##
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
            $apiClient->setBaseUrl($this->integration->base_url);
            $apiClient->setDefaultHeaders($this->defaultHeaders);
            
            $this->apiClient = $apiClient;
            $this->integration = $integration;

            parent::__construct($apiClient);
        }

        public function authenticate(): void
        {
            // Write your authentication logic here.
            // We predefined the OAuth Authorization Code Grant, OAuth Client Credential Grant and a API Key flow. You can use them in this method, just like the examples below.
            // The package is checking this function everytime it is making a request to the API.
            // Make sure you write it with that in mind if you're building a custom authentication flow here.
            
            // Make sure you set the Authentication Headers in this function.
            $authHeaders = $oAuthAuthorizationCodeFlow->getAuthenticationHeaders();
            $this->apiClient->setAuthenticationHeaders($authHeaders);
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

## Available methods on IntegrationService ##

| Method    | Description |
| -------- | ------- |
| getIntegrationName()  | Returns the name of the integration    |
| call(string $method, string $endpoint, array $body = [], array $headers = []) | Make a request to the API of the integration. Return the response in the right format.    |

## Available methods on the Integration model ##

| Method    | Description |
| -------- | ------- |
| setCredentials(array $credentials)  | Set a new array with credentials. This method overrides the already existing credentials set.    |
| addCredential(string $key, string $value)  | Add a credential to the set of credentials. If the credential already exists, it replaces it.    |
| removeCredential(string $key)  | Remove a credential attribute from the set of credentials.   |
| getCredential(string $key)  | Returns the decrypted value of a credential attribute.    |

## Acknowledgements ##
 - [Brendan&MacKenzie](https://www.brendan-mackenzie.com)

 ## Authors

- [@wouterdeberg](https://github.com/wouterdeberg)
- [@Brendan-MacKenzie](https://github.com/Brendan-MacKenzie)

## Issues
- [Report an issue here](https://github.com/Brendan-MacKenzie/Integration-Manager/issues/new)
- [List of open issues](https://github.com/Brendan-MacKenzie/Integration-Manager/issues)
