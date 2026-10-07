<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\PhpLocationIq\Collections\LocationVOCollection;
use AndyDefer\PhpLocationIq\Enums\DirectionsProfile;
use AndyDefer\PhpLocationIq\Enums\GeometriesType;
use AndyDefer\PhpLocationIq\Enums\OverviewType;
use AndyDefer\PhpLocationIq\Records\DirectionsRecord;
use AndyDefer\PhpLocationIq\ValueObjects\LocationVO;
use Illuminate\Validation\Rule;

final class DirectionsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'coordinates' => ['required', 'array', 'min:2', 'max:25'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
            'profile' => ['nullable', Rule::in(['driving', 'walking'])],
            'overview' => ['nullable', Rule::in(['simplified', 'full', 'false'])],
            'steps' => ['nullable', 'boolean'],
            'alternatives' => ['nullable', 'boolean'],
            'geometries' => ['nullable', Rule::in(['polyline', 'polyline6', 'geojson'])],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $collection = new LocationVOCollection;

        foreach ($this->input('coordinates') as $pair) {
            $collection->add(LocationVO::fromArray([(float) $pair[0], (float) $pair[1]]));
        }

        return new DirectionsRecord(
            coordinates: $collection,
            profile: DirectionsProfile::from($this->input('profile') ?? 'driving'),
            overview: OverviewType::from($this->input('overview') ?? 'simplified'),
            steps: (bool) $this->input('steps', false),
            alternatives: (bool) $this->input('alternatives', false),
            geometries: GeometriesType::from($this->input('geometries') ?? 'polyline'),
        );
    }
}
