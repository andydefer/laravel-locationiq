<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq;

use AndyDefer\LaravelLocationIq\Configs\LocationIqConfig;
use AndyDefer\LaravelLocationIq\Contracts\LocationIqConfigInterface;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use AndyDefer\PhpLocationIq\LocationIqClient;
use AndyDefer\PhpLocationIq\NominatimClient;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;

final class LocationIqServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/locationiq.php',
            'locationiq',
        );

        $this->registerConfig();
        $this->registerLocationIqClient();
        $this->registerNominatimClient();
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/locationiq.php' => config_path('locationiq.php'),
        ], 'laravel-locationiq-config');

        $this->publishes([
            __DIR__.'/../routes/api.php' => base_path('routes/locationiq.php'),
        ], 'laravel-locationiq-routes');
    }

    private function registerConfig(): void
    {
        $this->app->singleton(LocationIqConfig::class, function ($app): LocationIqConfig {
            return new LocationIqConfig($app['config']);
        });

        $this->app->bind(LocationIqConfigInterface::class, LocationIqConfig::class);
    }

    private function registerLocationIqClient(): void
    {
        $this->app->singleton(LocationIqClient::class, function ($app): LocationIqClient {
            /** @var LocationIqConfigInterface $config */
            $config = $app->make(LocationIqConfigInterface::class);

            return new LocationIqClient(
                apiKey: $config->getApiKey(),
                baseUrl: $config->getBaseUrl(),
            );
        });

        $this->app->bind(LocationIqClientInterface::class, function ($app): LocationIqClientInterface {
            /** @var LocationIqConfigInterface $config */
            $config = $app->make(LocationIqConfigInterface::class);

            return $app->make($config->getLocationIqClientFqcn());
        });
    }

    private function registerNominatimClient(): void
    {
        $this->app->singleton(NominatimClient::class, function ($app): NominatimClient {
            /** @var LocationIqConfigInterface $config */
            $config = $app->make(LocationIqConfigInterface::class);

            $client = new NominatimClient(
                agent: $app->make(Agent::class),
                baseUrl: $config->getNominatimBaseUrl(),
            );

            $client->setUserAgent($config->getUserAgent());

            return $client;
        });

        $this->app->bind(NominatimClientInterface::class, function ($app): NominatimClientInterface {
            /** @var LocationIqConfigInterface $config */
            $config = $app->make(LocationIqConfigInterface::class);

            return $app->make($config->getNominatimClientFqcn());
        });
    }
}
