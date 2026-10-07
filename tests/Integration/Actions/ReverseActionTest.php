<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests\Integration\Actions;

use AndyDefer\LaravelLocationIq\Http\Actions\ReverseAction;
use AndyDefer\LaravelLocationIq\Http\Requests\ReverseRequest;
use AndyDefer\LaravelLocationIq\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class ReverseActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/locationiq-reverse', action_route(
            ReverseRequest::class,
            ReverseAction::class,
        ))->name('locationiq.reverse');
    }

    public function test_it_returns_address_on_success(): void
    {
        // Arrange
        $this->nominatimClient->addReverseSuccessResponse([
            'licence' => 'Data © OpenStreetMap contributors',
            'osm_type' => 'way',
            'osm_id' => 434892314,
            'lat' => '-4.3619926',
            'lon' => '15.2185794',
            'category' => 'highway',
            'type' => 'residential',
            'place_rank' => 26,
            'importance' => 0.0534,
            'addresstype' => 'road',
            'name' => '',
            'display_name' => 'Kasi, Lukunga, Ngaliema, Kinshasa',
            'address' => [
                'city_district' => 'Kasi',
                'municipality' => 'Ngaliema',
                'state' => 'Kinshasa',
                'country' => 'République démocratique du Congo',
                'country_code' => 'cd',
            ],
            'boundingbox' => ['-4.3631191', '-4.3619147', '15.2171727', '15.2186610'],
        ]);

        // Act
        $response = $this->postJson('/api/locationiq-reverse', [
            'lat' => -4.3617,
            'lon' => 15.2183,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('reverse.displayName', 'Kasi, Lukunga, Ngaliema, Kinshasa');
        $response->assertJsonPath('reverse.address.cityDistrict', 'Kasi');
        $response->assertJsonPath('reverse.address.countryCode', 'cd');
    }

    public function test_it_validates_latitude_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-reverse', [
            'lat' => 200,
            'lon' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lat']);
    }

    public function test_it_validates_zoom_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-reverse', [
            'lat' => 10,
            'lon' => 10,
            'zoom' => 30,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['zoom']);
    }

    public function test_it_returns_not_found_when_unable_to_geocode(): void
    {
        // Arrange
        $this->nominatimClient->addReverseErrorResponse(200, 'Unable to geocode');

        // Act
        $response = $this->postJson('/api/locationiq-reverse', [
            'lat' => 0,
            'lon' => 0,
        ]);

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('errorCode', 'UNABLE_TO_GEOCODE');
    }
}
