<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Contracts;

use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Enums\NominatimBaseUrl;

interface LocationIqConfigInterface
{
    public function getApiKey(): string;

    public function getBaseUrl(): LocationIqBaseUrl;

    /**
     * @return class-string<LocationIqClientInterface>
     */
    public function getLocationIqClientFqcn(): string;

    public function getNominatimBaseUrl(): NominatimBaseUrl;

    public function getUserAgent(): string;

    /**
     * @return class-string<NominatimClientInterface>
     */
    public function getNominatimClientFqcn(): string;

    public function getDefaultDirectionsProfile(): string;

    public function getDefaultAcceptLanguage(): ?string;
}