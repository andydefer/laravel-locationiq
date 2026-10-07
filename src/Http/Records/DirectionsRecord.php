<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Traits\Hydratable;
use AndyDefer\PhpLocationIq\Collections\LocationVOCollection;
use AndyDefer\PhpLocationIq\Enums\DirectionsProfile;
use AndyDefer\PhpLocationIq\Enums\GeometriesType;
use AndyDefer\PhpLocationIq\Enums\OverviewType;
use AndyDefer\PhpLocationIq\Records\DirectionsRecord as ApiDirectionsRecord;
use AndyDefer\PhpLocationIq\ValueObjects\LocationVO;

final class DirectionsRecord extends AbstractRecord
{
    use Hydratable;

    /**
     * @param  array<int, array{0: float, 1: float}>  $coordinates
     */
    public function __construct(
        public readonly array $coordinates,
        public readonly string $profile = 'driving',
        public readonly string $overview = 'simplified',
        public readonly bool $steps = false,
        public readonly bool $alternatives = false,
        public readonly string $geometries = 'polyline',
    ) {}

    public function toApiRecord(): ApiDirectionsRecord
    {
        $collection = new LocationVOCollection;

        foreach ($this->coordinates as $pair) {
            $collection->add(LocationVO::fromArray($pair));
        }

        return new ApiDirectionsRecord(
            coordinates: $collection,
            profile: DirectionsProfile::from($this->profile),
            overview: OverviewType::from($this->overview),
            steps: $this->steps,
            alternatives: $this->alternatives,
            geometries: GeometriesType::from($this->geometries),
        );
    }
}
