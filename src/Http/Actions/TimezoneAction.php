<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Records\TimezoneRecord;

final class TimezoneAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var TimezoneRecord $request */
        $response = $this->client->getTimezone($request);

        if ($response->hasError()) {
            return ErrorCode::fromApiMessage($response->getError())
                ->toJsonResponseFactory();
        }

        return ResponseFactory::json($response->getData());
    }
}
