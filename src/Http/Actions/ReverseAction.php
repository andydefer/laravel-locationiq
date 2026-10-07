<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use AndyDefer\PhpLocationIq\Records\Nominatim\ReverseRecord;

/**
 * HTTP action that resolves an address from a coordinate pair via Nominatim.
 *
 * The incoming request must carry a {@see ReverseRecord}. The action forwards
 * it to the Nominatim client, maps any API-side error to an {@see ErrorCode},
 * and returns the address payload on success.
 */
final class ReverseAction extends AbstractAction
{
    public function __construct(
        private readonly NominatimClientInterface $client,
    ) {}

    /**
     * {@inheritDoc}
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var ReverseRecord $request */
        $response = $this->client->reverse($request);

        if ($response->hasError()) {
            return ErrorCode::fromApiMessage($response->getError())
                ->toJsonResponseFactory();
        }

        return ResponseFactory::json($response->getData());
    }
}
