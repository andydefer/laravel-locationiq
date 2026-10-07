<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;

/**
 * HTTP action that returns the remaining request credits for the current UTC day.
 *
 * Delegates the call to the LocationIQ client, maps any API-side error to an
 * {@see ErrorCode} and returns the balance payload on success.
 */
final class BalanceAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    /**
     * {@inheritDoc}
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $response = $this->client->getBalance();

        if ($response->hasError()) {
            return ErrorCode::fromApiMessage($response->getError())
                ->toJsonResponseFactory();
        }

        return ResponseFactory::json($response->getData());
    }
}
