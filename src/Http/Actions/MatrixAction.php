<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Records\MatrixRecord;
use InvalidArgumentException;

/**
 * HTTP action that computes a travel matrix between coordinates.
 *
 * The incoming request must carry a {@see MatrixRecord}. The action
 * forwards it to the LocationIQ client, catches SDK-level validation
 * errors (coordinates, sources, destinations, fallback), maps any
 * API-side error to an {@see ErrorCode}, and returns the matrices
 * payload on success.
 */
final class MatrixAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    /**
     * {@inheritDoc}
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var MatrixRecord $request */
        try {
            $response = $this->client->getMatrix($request);
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
