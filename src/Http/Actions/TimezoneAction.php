<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelLocationIq\Enums\ErrorCode;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Records\TimezoneRecord;

/**
 * HTTP action that resolves the time zone information for a coordinate pair.
 *
 * The incoming request must carry a {@see TimezoneRecord}. The action forwards
 * it to the LocationIQ client, maps any API-side error to an {@see ErrorCode},
 * and returns the time zone payload on success.
 */
final class TimezoneAction extends AbstractAction
{
    public function __construct(
        private readonly LocationIqClientInterface $client,
    ) {}

    /**
     * {@inheritDoc}
     */
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
