<?php

declare(strict_types=1);

use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Enums\NominatimBaseUrl;
use AndyDefer\PhpLocationIq\LocationIqClient;
use AndyDefer\PhpLocationIq\NominatimClient;

return [
    'api_key' => env('LOCATIONIQ_API_KEY', ''),

    'base_url' => env('LOCATIONIQ_BASE_URL', LocationIqBaseUrl::US1->value),

    'locationiq_client_fqcn' => LocationIqClient::class,

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', NominatimBaseUrl::PUBLIC->value),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'andydefer/laravel-locationiq'),
        'accept_language' => env('NOMINATIM_ACCEPT_LANGUAGE', 'fr'),
    ],

    'nominatim_client_fqcn' => NominatimClient::class,

    'directions' => [
        'profile' => env('LOCATIONIQ_DIRECTIONS_PROFILE', 'driving'),
        'accept_language' => env('LOCATIONIQ_ACCEPT_LANGUAGE'),
    ],
];
