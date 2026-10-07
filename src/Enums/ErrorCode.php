<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Enums;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\Nemesis\Contracts\ErrorDescribable;
use AndyDefer\Nemesis\Datas\ErrorResponseData;
use AndyDefer\PhpVo\Enums\HttpStatusCode;

enum ErrorCode: string implements ErrorDescribable
{
    // Client errors (HTTP 400 / 422)
    case INVALID_REQUEST = 'INVALID_REQUEST';
    case INVALID_KEY = 'INVALID_KEY';
    case ACCESS_RESTRICTED = 'ACCESS_RESTRICTED';
    case UNABLE_TO_GEOCODE = 'UNABLE_TO_GEOCODE';
    case RATE_LIMITED_DAY = 'RATE_LIMITED_DAY';
    case INVALID_OPTIONS = 'INVALID_OPTIONS';
    case INVALID_COORDINATES = 'INVALID_COORDINATES';
    case MISSING_API_KEY = 'MISSING_API_KEY';

    // Server errors (HTTP 500)
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    /**
     * {@inheritDoc}
     */
    public function getHttpStatusCode(): HttpStatusCode
    {
        return match ($this) {
            self::INVALID_REQUEST,
            self::INVALID_OPTIONS,
            self::INVALID_COORDINATES,
            self::MISSING_API_KEY => HttpStatusCode::UNPROCESSABLE_ENTITY,

            self::INVALID_KEY => HttpStatusCode::UNAUTHORIZED,
            self::ACCESS_RESTRICTED => HttpStatusCode::FORBIDDEN,
            self::UNABLE_TO_GEOCODE => HttpStatusCode::NOT_FOUND,
            self::RATE_LIMITED_DAY => HttpStatusCode::TOO_MANY_REQUESTS,
            self::UNKNOWN_ERROR => HttpStatusCode::INTERNAL_SERVER_ERROR,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::INVALID_REQUEST => 'Invalid Request',
            self::INVALID_KEY => 'Invalid Key',
            self::ACCESS_RESTRICTED => 'Access restricted',
            self::UNABLE_TO_GEOCODE => 'Unable to geocode',
            self::RATE_LIMITED_DAY => 'Rate Limited Day',
            self::INVALID_OPTIONS => 'InvalidOptions',
            self::INVALID_COORDINATES => 'Invalid coordinates',
            self::MISSING_API_KEY => 'Missing API key',
            self::UNKNOWN_ERROR => 'Unknown error - Please try again after some time',
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->getMessage();
    }

    /**
     * {@inheritDoc}
     */
    public function toResponseData(
        ?string $message = null,
        array|StrictAssociative|StrictDataObject|null $errors = null,
    ): ErrorResponseData {
        return ErrorResponseData::from([
            'errorCode' => $this,
            'message' => $message ?? $this->getMessage(),
            'status' => $this->getHttpStatusCode(),
            'errors' => $errors,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function toJsonResponseFactory(
        ?string $message = null,
        array|StrictAssociative|StrictDataObject|null $errors = null,
    ): ResponseFactory {
        return ResponseFactory::json(
            $this->toResponseData($message, $errors),
            $this->getHttpStatusCode(),
        );
    }

    /**
     * Map a raw LocationIQ/Nominatim error string to an ErrorCode.
     */
    public static function fromApiMessage(string $message): self
    {
        return match (strtolower(trim($message))) {
            'invalid request' => self::INVALID_REQUEST,
            'invalid key' => self::INVALID_KEY,
            'access restricted' => self::ACCESS_RESTRICTED,
            'unable to geocode' => self::UNABLE_TO_GEOCODE,
            'rate limited day' => self::RATE_LIMITED_DAY,
            'invalidoptions' => self::INVALID_OPTIONS,
            'unknown error - please try again after some time' => self::UNKNOWN_ERROR,
            default => self::UNKNOWN_ERROR,
        };
    }
}
