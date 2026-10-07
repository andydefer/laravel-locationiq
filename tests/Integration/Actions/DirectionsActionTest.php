<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests\Integration\Actions;

use AndyDefer\LaravelLocationIq\Http\Actions\DirectionsAction;
use AndyDefer\LaravelLocationIq\Http\Requests\DirectionsRequest;
use AndyDefer\LaravelLocationIq\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class DirectionsActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/locationiq-directions', action_route(
            DirectionsRequest::class,
            DirectionsAction::class,
        ))->name('locationiq.directions');
    }

    public function test_it_returns_route_on_success(): void
    {
        // Arrange
        $this->locationIqClient->addDirectionsSuccessResponse([
            'code' => 'ok',
            'waypoints' => [
                'distance' => 159.13,
                'location' => [15.3222, -4.3250],
                'name' => 'Avenue du Kasaï',
            ],
            'routes' => [
                'legs' => [
                    'steps' => [],
                    'weight' => 22.7,
                    'distance' => 104.2,
                    'summary' => '',
                    'duration' => 24.8,
                ],
                'weight_name' => 'routability',
                'geometry' => 'abc123',
                'weight' => 22.6,
                'distance' => 104.8,
                'duration' => 24.8,
            ],
        ]);

        // Act
        $response = $this->postJson('/api/locationiq-directions', [
            'coordinates' => [
                [15.3222, -4.3250],
                [15.4446, -4.3858],
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('directions.code', 'ok');
    }

    public function test_it_requires_at_least_two_coordinates(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-directions', [
            'coordinates' => [[15.3222, -4.3250]],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['coordinates']);
    }

    public function test_it_rejects_more_than_twenty_five_coordinates(): void
    {
        // Arrange
        $coordinates = [];
        for ($i = 0; $i < 26; $i++) {
            $coordinates[] = [15.3222 + $i, -4.3250];
        }

        // Act
        $response = $this->postJson('/api/locationiq-directions', [
            'coordinates' => $coordinates,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['coordinates']);
    }

    public function test_it_rejects_invalid_profile(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-directions', [
            'coordinates' => [
                [15.3222, -4.3250],
                [15.4446, -4.3858],
            ],
            'profile' => 'flying',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile']);
    }

    public function test_it_returns_error_when_api_says_invalid_options(): void
    {
        // Arrange
        $this->locationIqClient->addDirectionsErrorResponse('InvalidOptions');

        // Act
        $response = $this->postJson('/api/locationiq-directions', [
            'coordinates' => [
                [15.3222, -4.3250],
                [15.4446, -4.3858],
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errorCode', 'INVALID_OPTIONS');
    }
}
