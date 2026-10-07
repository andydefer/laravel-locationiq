<?php

declare(strict_types=1);

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\LaravelLocationIq\Http\Actions\BalanceAction;
use AndyDefer\LaravelLocationIq\Http\Actions\DirectionsAction;
use AndyDefer\LaravelLocationIq\Http\Actions\ReverseAction;
use AndyDefer\LaravelLocationIq\Http\Actions\TimezoneAction;
use AndyDefer\LaravelLocationIq\Http\Requests\DirectionsRequest;
use AndyDefer\LaravelLocationIq\Http\Requests\ReverseRequest;
use AndyDefer\LaravelLocationIq\Http\Requests\TimezoneRequest;
use Illuminate\Support\Facades\Route;

Route::prefix('locationiq')
    ->name('locationiq.')
    ->group(function () {
        Route::post('/balance', action_route(EmptyRequest::class, BalanceAction::class))
            ->name('balance');

        Route::post('/timezone', action_route(TimezoneRequest::class, TimezoneAction::class))
            ->name('timezone');

        Route::post('/directions', action_route(DirectionsRequest::class, DirectionsAction::class))
            ->name('directions');

        Route::post('/reverse', action_route(ReverseRequest::class, ReverseAction::class))
            ->name('reverse');
    });
