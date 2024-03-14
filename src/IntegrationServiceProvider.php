<?php

namespace BrendanMacKenzie\IntegrationManager;

use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
  public function register()
  {
    $this->mergeConfigFrom(__DIR__.'/config/integrations.php', 'integrations');
  }

  public function boot()
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__.'/config/integrations.php' => config_path('integrations.php'),
      ], 'integration-config');

      $this->publishes([
        __DIR__.'/database/migrations/' => database_path('migrations')
    ], 'integration-migrations');
    }

    $this->loadMigrationsFrom(__DIR__.'/database/migrations');

    $this->loadRoutesFrom(__DIR__.'/routes/web.php');
  }
}