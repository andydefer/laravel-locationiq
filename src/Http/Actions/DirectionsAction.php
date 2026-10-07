<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Records\DirectionsRecord;
use InvalidArgumentException;

/**
 * HTTP action that computes a route between two or more coordinates.
 *
 * The incoming request must carry a {@see DirectionsRecord}. The action
 * forwards it to the LocationIQ client, catches SDK-level coordinate
 * validation errors, maps any API-side error to an {@see ErrorCode}, and
 * returns the route payload on success.
 */
final class DirectionsAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    /**
     * {@inheritDoc}
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var DirectionsRecord $request */
        try {
            $response = $this->client->getDirections($request);
        } catch (InvalidArgumentException $exception) {
            return ErrorCode::INVALID_COORDINATES
                ->toJsonResponseFactory($exception->getMessage());
        }

        if ($response->hasError()) {
            return ErrorCode::fromApiMessage($response->getError())
                ->toJsonResponseFactory();
        }

        return ResponseFactory::json($response->getData());
    }
}
