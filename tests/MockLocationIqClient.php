<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Tests;

use AndyDefer\PhpClient\Clients\ClientService;
use AndyDefer\PhpClient\Enums\ContentType;
use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Contracts\Responses\BalanceResponseInterface;
use AndyDefer\PhpLocationIq\Contracts\Responses\DirectionsResponseInterface;
use AndyDefer\PhpLocationIq\Contracts\Responses\MatrixResponseInterface;
use AndyDefer\PhpLocationIq\Contracts\Responses\TimezoneResponseInterface;
use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Records\DirectionsRecord;
use AndyDefer\PhpLocationIq\Records\MatrixRecord;
use AndyDefer\PhpLocationIq\Records\TimezoneRecord;
use AndyDefer\PhpLocationIq\Requests\BalanceRequest;
use AndyDefer\PhpLocationIq\Requests\DirectionsRequest;
use AndyDefer\PhpLocationIq\Requests\MatrixRequest;
use AndyDefer\PhpLocationIq\Requests\TimezoneRequest;
use AndyDefer\PhpLocationIq\Responses\BalanceResponse;
use AndyDefer\PhpLocationIq\Responses\DirectionsResponse;
use AndyDefer\PhpLocationIq\Responses\MatrixResponse;
use AndyDefer\PhpLocationIq\Responses\TimezoneResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

final class MockLocationIqClient implements LocationIqClientInterface
{
    private MockHandler $mockHandler;

    private LocationIqBaseUrl $baseUrl;

    public function __construct(
        private readonly string $apiKey,
        LocationIqBaseUrl $baseUrl = LocationIqBaseUrl::US1,
    ) {
        $this->mockHandler = new MockHandler;
        $this->baseUrl = $baseUrl;
    }

    public function setBaseUrl(LocationIqBaseUrl $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getTimezone(TimezoneRecord $record): TimezoneResponseInterface
    {
        return $this->dispatch(
            TimezoneRequest::class,
            [$record, $this->baseUrl, $this->apiKey],
            TimezoneResponse::class,
        );
    }

    public function getDirections(DirectionsRecord $record): DirectionsResponseInterface
    {
        return $this->dispatch(
            DirectionsRequest::class,
            [$record, $this->baseUrl, $this->apiKey],
            DirectionsResponse::class,
        );
    }

    public function getMatrix(MatrixRecord $record): MatrixResponseInterface
    {
        return $this->dispatch(
            MatrixRequest::class,
            [$record, $this->baseUrl, $this->apiKey],
            MatrixResponse::class,
        );
    }

    public function getBalance(): BalanceResponseInterface
    {
        return $this->dispatch(
            BalanceRequest::class,
            [$this->baseUrl, $this->apiKey],
            BalanceResponse::class,
        );
    }

    public function addResponse(int $status, array $headers, string $body): void
    {
        $this->mockHandler->append(new Response($status, $headers, $body));
    }

    public function addSuccessResponse(array $data): void
    {
        $this->addResponse(200, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function addErrorResponse(int $status, string $error): void
    {
        $this->addResponse($status, ['Content-Type' => 'application/json'], json_encode(['error' => $error], JSON_THROW_ON_ERROR));
    }

    public function addTimezoneSuccessResponse(
        string $name = 'Asia/Kolkata',
        int $nowInDst = 0,
        int $offsetSec = 19800,
        string $shortName = 'IST',
        string $fullName = 'India Standard Time',
    ): void {
        $this->addSuccessResponse([
            'timezone' => [
                'name' => $name,
                'now_in_dst' => $nowInDst,
                'offset_sec' => $offsetSec,
                'short_name' => $shortName,
                'full_name' => $fullName,
            ],
        ]);
    }

    public function addBalanceSuccessResponse(int $day = 30000): void
    {
        $this->addSuccessResponse([
            'status' => 'ok',
            'balance' => ['day' => $day],
        ]);
    }

    public function addDirectionsSuccessResponse(array $data): void
    {
        $this->addSuccessResponse($data);
    }

    public function addDirectionsErrorResponse(string $code): void
    {
        $this->addSuccessResponse(['code' => $code]);
    }

    /**
     * Appends a successful Matrix response to the mock queue.
     *
     * @param  array<int, array<int, float|null>>|null  $durations  Durations matrix (seconds).
     * @param  array<int, array<int, float|null>>|null  $distances  Distances matrix (meters).
     * @param  array<int, array<string, mixed>>  $sources  Resolved source waypoints.
     * @param  array<int, array<string, mixed>>  $destinations  Resolved destination waypoints.
     */
    public function addMatrixSuccessResponse(
        ?array $durations = null,
        ?array $distances = null,
        array $sources = [],
        array $destinations = [],
    ): void {
        $payload = ['code' => 'Ok'];

        if ($durations !== null) {
            $payload['durations'] = $durations;
        }

        if ($distances !== null) {
            $payload['distances'] = $distances;
        }

        $payload['sources'] = $sources;
        $payload['destinations'] = $destinations;

        $this->addSuccessResponse($payload);
    }

    /**
     * Appends a Matrix error response to the mock queue.
     *
     * @param  string  $code  Error code (`NoTable`, `NotImplemented`).
     */
    public function addMatrixErrorResponse(string $code): void
    {
        $this->addSuccessResponse(['code' => $code]);
    }

    /**
     * @param  class-string  $requestClass
     * @param  array<int, mixed>  $requestArgs
     * @param  class-string  $responseClass
     */
    private function dispatch(string $requestClass, array $requestArgs, string $responseClass): mixed
    {
        $request = new $requestClass(...$requestArgs);
        $request->getHeaders()->setAccept(ContentType::JSON);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);
        $clientService = new ClientService($guzzleClient);

        return $clientService->get(
            $request->getUrl()->getValue(),
            $request,
            $responseClass,
        );
    }
}
