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

final class DirectionsAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var DirectionsRecord $request */
        try {
            $response = $this->client->getDirections($request);
        } catch (InvalidArgumentException $e) {
            return ErrorCode::INVALID_COORDINATES
                ->toJsonResponseFactory($e->getMessage());
        }

        if ($response->hasError()) {
            return ErrorCode::fromApiMessage($response->getError())
                ->toJsonResponseFactory();
        }

        return ResponseFactory::json($response->getData());
    }
}
