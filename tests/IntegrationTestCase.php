<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests;

use AndyDefer\Actions\ActionServiceProvider;
use AndyDefer\LaravelLocationIq\LocationIqServiceProvider;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class IntegrationTestCase extends Orchestra
{
    protected MockLocationIqClient $locationIqClient;

    protected MockNominatimClient $nominatimClient;

    protected function setUp(): void
    {
        parent::setUp();

        Route::clearResolvedInstances();
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->locationIqClient = new MockLocationIqClient('pk.test-token');
        $this->nominatimClient = new MockNominatimClient('test-agent/1.0');

        $this->app->instance(LocationIqClientInterface::class, $this->locationIqClient);
        $this->app->instance(NominatimClientInterface::class, $this->nominatimClient);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LocationIqServiceProvider::class,
            ActionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('locationiq.api_key', 'pk.test-token');
        $app['config']->set('locationiq.base_url', 'https://us1.locationiq.com');
        $app['config']->set('locationiq.nominatim.user_agent', 'test-agent/1.0');
        $app['config']->set('locationiq.nominatim.base_url', 'https://nominatim.openstreetmap.org');
    }
}
