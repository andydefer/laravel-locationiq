<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests\Integration\Actions;

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\LaravelLocationIq\Http\Actions\BalanceAction;
use AndyDefer\LaravelLocationIq\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class BalanceActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/locationiq-balance', action_route(
            EmptyRequest::class,
            BalanceAction::class,
        ))->name('locationiq.balance');
    }

    public function test_it_returns_day_balance_on_success(): void
    {
        // Arrange: the API will answer 200 with a positive balance
        $this->locationIqClient->addBalanceSuccessResponse(30000);

        // Act: hit the route
        $response = $this->postJson('/api/locationiq-balance');

        // Assert: 200 and balance.day exposed
        $response->assertOk();
        $response->assertJsonPath('balance.day', 30000);
    }

    public function test_it_returns_unauthorized_when_api_key_is_invalid(): void
    {
        // Arrange: the API will answer with an Invalid Key error
        $this->locationIqClient->addErrorResponse(200, 'Invalid Key');

        // Act
        $response = $this->postJson('/api/locationiq-balance');

        // Assert: mapped to 401 + normalized ErrorResponseData
        $response->assertStatus(401);
        $response->assertJsonPath('errorCode', 'INVALID_KEY');
        $response->assertJsonPath('message', 'Invalid Key');
        $response->assertJsonPath('status', 401);
    }

    public function test_it_returns_too_many_requests_when_rate_limited(): void
    {
        // Arrange
        $this->locationIqClient->addErrorResponse(200, 'Rate Limited Day');

        // Act
        $response = $this->postJson('/api/locationiq-balance');

        // Assert
        $response->assertStatus(429);
        $response->assertJsonPath('errorCode', 'RATE_LIMITED_DAY');
    }
}
