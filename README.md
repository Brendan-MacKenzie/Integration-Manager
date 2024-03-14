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
5. Run `php artisan migrate` to migrate the integration tables.

## Define an integration for a project model ##
To link an integration and it's credentials to a model inside your own project, simply create an Integration model linked to your model (polyformic relation) and make sure you link the right IntegrationOption.

```
    use BrendanMacKenzie\IntegrationManager\Models\Integration;

    // Find the right integration.
    $integrationOption = IntegrationOption::where('name', 'whatsapp')->first();

    // Create an integration rule for your model.
    $integration = Integration::create([
        'integration_option_id' => $integrationOption->id,
    ]);

    // Associate your model to the integration.
    $integration->owner()->associate($yourModel);

    // Store the credentials for the integration with your own integration service (like the example down below: WhatsAppService.php):
    $credentials = [
        'API_ID' => 'xxxx1234',
        'API_KEY' => '4321aaaa',
        'user_id' => 1234,
    ];

    $this->whatsAppService->setCredentials($credentials);
    
```

## Building an integration ##
To implement an API integration in your Laravel project you can extend the abstract class `BrendanMacKenzie\IntegrationManager\Utils\IntegrationService.php` inside your own integration service class.

For example if you want to build an integration with the WhatsApp API, you can create your own `WhatsAppService.php` class like this:

```
    <?php

    namespace App\Services;

    use BrendanMacKenzie\IntegrationManager\Utils\IntegrationService;
    use BrendanMacKenzie\IntegrationManager\Models\Integration;

    class WhatsAppService extends IntegrationService
    {
        public function __construct(Integration $integration)
        {
            $this->integration = $integration;
            $this->baseUrl = 'https://api.whatsapp.com';
            $this->defaultHeaders = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            parent::construct();
        }

        public function authenticate(): void
        {
            // Write your authentication logic here..
            // The package is checking this function everytime it is making a request to the API.
            // Make sure you write it with that in mind.
        }

        // Example of a request on the WhatsApp API build with Integration Manager:

        public function getChats()
        {
            // Build a body for the request with a simple array.
            $body = [
                'user_id' => 1234,
            ];

            $headers = [
                // Get a credential value you stored in the integration model.
                'Extra-Token' => $this->getCredential('extra_token');
            ];

            // Build all requests with one simple method..
            $response = $this->call('GET', '/chats', $body, $headers);

            return $response->data;
        }

    }
```

## Available methods ##

| Method    | Description |
| -------- | ------- |
| getIntegrationName()  | Returns the name of the integration    |
| setCredentials(array $credentials) | Securely store a set of credentials of the integration for your model. Data is encrypted.     |
| addCredential(string $key, string $value)    | Securely add one credential to the set of credentials of the integration. Data is encrypted.    |
| removeCredential(string $key)    | Remove one credential on the set of credentials for the integration.    |
| getCredential(string $key)    | Retrieve the decrypted value of a credential.   |
| call(string $method, string $endpoint, array $body = [], array $headers = []) | Make a request to the API of the integration. Return the response in the right format.    |

## Tools ##

- TODO: Build a default OAuth flow for in the authenticate logic. Keep all the authentication flows in mind.<br />
- TODO: Build a default API Key and ID flow for in the authenticate logic.

## Acknowledgements ##
 - [Brendan&MacKenzie](https://www.brendan-mackenzie.com)

 ## Authors

- [@wouterdeberg](https://github.com/wouterdeberg)
- [@Brendan-MacKenzie](https://github.com/Brendan-MacKenzie)

## Issues
- [Report an issue here](https://github.com/Brendan-MacKenzie/Integration-Manager/issues/new)
- [List of open issues](https://github.com/Brendan-MacKenzie/Integration-Manager/issues)
