<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Configs;

use AndyDefer\LaravelLocationIq\Contracts\LocationIqConfigInterface;
use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Enums\NominatimBaseUrl;
use AndyDefer\PhpLocationIq\LocationIqClient;
use AndyDefer\PhpLocationIq\NominatimClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class LocationIqConfig implements LocationIqConfigInterface
{
    private const DEFAULT_API_KEY = '';

    private const DEFAULT_BASE_URL = LocationIqBaseUrl::US1;

    private const DEFAULT_LOCATIONIQ_CLIENT_FQCN = LocationIqClient::class;

    private const DEFAULT_NOMINATIM_BASE_URL = NominatimBaseUrl::PUBLIC;

    private const DEFAULT_USER_AGENT = 'andydefer/laravel-locationiq';

    private const DEFAULT_NOMINATIM_CLIENT_FQCN = NominatimClient::class;

    private const DEFAULT_DIRECTIONS_PROFILE = 'driving';

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function getApiKey(): string
    {
        return (string) $this->config->get('locationiq.api_key', self::DEFAULT_API_KEY);
    }

    public function getBaseUrl(): LocationIqBaseUrl
    {
        $raw = (string) $this->config->get(
            'locationiq.base_url',
            self::DEFAULT_BASE_URL->value,
        );

        return LocationIqBaseUrl::tryFrom($raw) ?? self::DEFAULT_BASE_URL;
    }

    public function getLocationIqClientFqcn(): string
    {
        return (string) $this->config->get(
            'locationiq.locationiq_client_fqcn',
            self::DEFAULT_LOCATIONIQ_CLIENT_FQCN,
        );
    }

    public function getNominatimBaseUrl(): NominatimBaseUrl
    {
        $raw = (string) $this->config->get(
            'locationiq.nominatim.base_url',
            self::DEFAULT_NOMINATIM_BASE_URL->value,
        );

        return NominatimBaseUrl::tryFrom($raw) ?? self::DEFAULT_NOMINATIM_BASE_URL;
    }

    public function getUserAgent(): string
    {
        return (string) $this->config->get(
            'locationiq.nominatim.user_agent',
            self::DEFAULT_USER_AGENT,
        );
    }

    public function getNominatimClientFqcn(): string
    {
        return (string) $this->config->get(
            'locationiq.nominatim_client_fqcn',
            self::DEFAULT_NOMINATIM_CLIENT_FQCN,
        );
    }

    public function getDefaultDirectionsProfile(): string
    {
        return (string) $this->config->get(
            'locationiq.directions.profile',
            self::DEFAULT_DIRECTIONS_PROFILE,
        );
    }

    public function getDefaultAcceptLanguage(): ?string
    {
        $value = $this->config->get('locationiq.directions.accept_language');

        return $value !== null ? (string) $value : null;
    }
}
