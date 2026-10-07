<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Traits\Hydratable;
use AndyDefer\PhpLocationIq\Records\TimezoneRecord as ApiTimezoneRecord;
use AndyDefer\PhpVo\ValueObjects\CoordinatesVO;
use AndyDefer\PhpVo\ValueObjects\Types\FloatVO;

final class TimezoneRecord extends AbstractRecord
{
    use Hydratable;

    public function __construct(
        public readonly float $lat,
        public readonly float $lon,
        public readonly ?int $timestamp = null,
    ) {}

    public function toApiRecord(): ApiTimezoneRecord
    {
        return new ApiTimezoneRecord(
            coordinates: new CoordinatesVO(
                FloatVO::from($this->lat),
                FloatVO::from($this->lon),
            ),
            timestamp: $this->timestamp,
        );
    }
}
