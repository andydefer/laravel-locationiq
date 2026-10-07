<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests\Integration\Actions;

use AndyDefer\LaravelLocationIq\Http\Actions\TimezoneAction;
use AndyDefer\LaravelLocationIq\Http\Requests\TimezoneRequest;
use AndyDefer\LaravelLocationIq\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class TimezoneActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/locationiq-timezone', action_route(
            TimezoneRequest::class,
            TimezoneAction::class,
        ))->name('locationiq.timezone');
    }

    public function test_it_returns_timezone_data_on_success(): void
    {
        // Arrange
        $this->locationIqClient->addTimezoneSuccessResponse();

        // Act
        $response = $this->postJson('/api/locationiq-timezone', [
            'lat' => 19.0760,
            'lon' => 72.8777,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('timezone.name', 'Asia/Kolkata');
        $response->assertJsonPath('timezone.offsetSeconds', 19800);
        $response->assertJsonPath('timezone.shortName', 'IST');
    }

    public function test_it_validates_latitude_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-timezone', [
            'lat' => 200,
            'lon' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lat']);
    }

    public function test_it_validates_longitude_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-timezone', [
            'lat' => 10,
            'lon' => 200,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lon']);
    }

    public function test_it_returns_error_when_api_says_unable_to_geocode(): void
    {
        // Arrange
        $this->locationIqClient->addErrorResponse(200, 'Unable to geocode');

        // Act
        $response = $this->postJson('/api/locationiq-timezone', [
            'lat' => 0,
            'lon' => 0,
        ]);

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('errorCode', 'UNABLE_TO_GEOCODE');
    }
}
