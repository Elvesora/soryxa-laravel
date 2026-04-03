<?php

namespace Elvesora\Soryxa;

use Illuminate\Support\ServiceProvider;

class SoryxaServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom(__DIR__ . '/../config/soryxa.php', 'soryxa');

        $this->app->singleton(SoryxaClient::class, function ($app) {
            $config = $app['config']['soryxa'];

            return new SoryxaClient(
                baseUrl: $config['base_url'],
                token: $config['token'],
                timeout: $config['timeout'] ?? 30,
                retries: $config['retries'] ?? 0,
                retryDelay: $config['retry_delay'] ?? 100,
                silentOnLimit: $config['silent_on_limit'] ?? false,
            );
        });

        $this->app->alias(SoryxaClient::class, 'soryxa');
    }

    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/soryxa.php' => config_path('soryxa.php'),
            ], 'soryxa-config');
        }
    }
}
