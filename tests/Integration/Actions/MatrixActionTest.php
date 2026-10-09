<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests\Integration\Actions;

use AndyDefer\LaravelLocationIq\Http\Actions\MatrixAction;
use AndyDefer\LaravelLocationIq\Http\Requests\MatrixRequest;
use AndyDefer\LaravelLocationIq\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class MatrixActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/locationiq-matrix', action_route(
            MatrixRequest::class,
            MatrixAction::class,
        ))->name('locationiq.matrix');
    }

    public function test_it_returns_duration_matrix_on_success(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixSuccessResponse(
            durations: [
                [0.0, 529.0, 185.7],
                [472.3, 0.0, 622.4],
                [197.7, 663.1, 0.0],
            ],
            sources: [
                [
                    'name' => 'Downing Street',
                    'distance' => 85.752389,
                    'location' => [-0.12643, 51.503164],
                    'hint' => 'hint-a',
                ],
            ],
            destinations: [
                [
                    'name' => 'King William Street',
                    'distance' => 0.069405,
                    'location' => [-0.0872, 51.509562],
                    'hint' => 'hint-b',
                ],
            ],
        );

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
                [-0.142001, 51.501284],
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('error', null);
        $this->assertEquals(529.0, $response->json('durations.rows.0.1'));
        $response->assertJsonPath('sources.0.name', 'Downing Street');
        $response->assertJsonPath('destinations.0.name', 'King William Street');
    }

    public function test_it_returns_durations_and_distances_when_requested(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixSuccessResponse(
            durations: [[0.0, 529.0], [472.3, 0.0]],
            distances: [[0.0, 3833.6], [3763.8, 0.0]],
        );

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'annotations' => ['duration', 'distance'],
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(529.0, $response->json('durations.rows.0.1'));
        $this->assertEquals(3833.6, $response->json('distances.rows.0.1'));
    }

    public function test_it_restricts_sources_and_destinations(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixSuccessResponse(
            durations: [[0.0, 529.0, 185.7]],
        );

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
                [-0.142001, 51.501284],
            ],
            'sources' => [0],
            'destinations' => [0, 1, 2],
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(529.0, $response->json('durations.rows.0.1'));
    }

    public function test_it_accepts_fallback_options(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixSuccessResponse(
            durations: [[0.0, 529.0]],
        );

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'fallback_speed' => 15.5,
            'fallback_coordinate' => 'snapped',
        ]);

        // Assert
        $response->assertOk();
    }

    public function test_it_requires_at_least_two_coordinates(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [[-0.127627, 51.503355]],
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
            $coordinates[] = [-0.127627 + $i, 51.503355];
        }

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => $coordinates,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['coordinates']);
    }

    public function test_it_rejects_unknown_annotation(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'annotations' => ['duration', 'invalid'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['annotations.1']);
    }

    public function test_it_rejects_invalid_fallback_speed(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'fallback_speed' => 0,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fallback_speed']);
    }

    public function test_it_rejects_invalid_profile(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'profile' => 'flying',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile']);
    }

    public function test_it_rejects_sources_index_out_of_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'sources' => [5],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sources.0']);
    }

    public function test_it_rejects_destinations_index_out_of_range(): void
    {
        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
            'destinations' => [10],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['destinations.0']);
    }

    public function test_it_returns_error_when_api_says_no_table(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixErrorResponse('NoTable');

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errorCode', 'NO_TABLE');
    }

    public function test_it_returns_error_when_api_says_not_implemented(): void
    {
        // Arrange
        $this->locationIqClient->addMatrixErrorResponse('NotImplemented');

        // Act
        $response = $this->postJson('/api/locationiq-matrix', [
            'coordinates' => [
                [-0.127627, 51.503355],
                [-0.087199, 51.509562],
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errorCode', 'NOT_IMPLEMENTED');
    }
}
