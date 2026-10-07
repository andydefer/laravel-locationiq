<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use AndyDefer\PhpLocationIq\Records\Nominatim\ReverseRecord;

final class ReverseAction extends AbstractAction
{
    public function __construct(
        private readonly NominatimClientInterface $client,
    ) {}

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
