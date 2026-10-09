<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\PhpLocationIq\Collections\LocationVOCollection;
use AndyDefer\PhpLocationIq\Collections\MatrixAnnotationCollection;
use AndyDefer\PhpLocationIq\Enums\DirectionsProfile;
use AndyDefer\PhpLocationIq\Enums\FallbackCoordinate;
use AndyDefer\PhpLocationIq\Enums\MatrixAnnotation;
use AndyDefer\PhpLocationIq\Records\MatrixRecord;
use AndyDefer\PhpLocationIq\ValueObjects\LocationVO;
use AndyDefer\PhpLocationIq\ValueObjects\MatrixOptionsVO;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class MatrixRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'coordinates' => ['required', 'array', 'min:2', 'max:25'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
            'profile' => ['nullable', Rule::enum(DirectionsProfile::class)],
            'annotations' => ['nullable', 'array', 'min:1'],
            'annotations.*' => ['required', Rule::enum(MatrixAnnotation::class)],
            'sources' => ['nullable', 'array', 'min:1'],
            'sources.*' => ['required', 'integer', 'min:0'],
            'destinations' => ['nullable', 'array', 'min:1'],
            'destinations.*' => ['required', 'integer', 'min:0'],
            'fallback_speed' => ['nullable', 'numeric', 'gt:0'],
            'fallback_coordinate' => ['nullable', Rule::enum(FallbackCoordinate::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $coordinates = $this->input('coordinates');

            if (! is_array($coordinates) || count($coordinates) === 0) {
                return;
            }

            $max = count($coordinates) - 1;

            foreach (['sources', 'destinations'] as $field) {
                $indexes = $this->input($field);

                if (! is_array($indexes)) {
                    continue;
                }

                foreach ($indexes as $position => $index) {
                    if (! is_int($index) || $index < 0 || $index > $max) {
                        $validator->errors()->add(
                            "{$field}.{$position}",
                            "The {$field}.{$position} field must be between 0 and {$max}."
                        );
                    }
                }
            }
        });
    }

    public function getRecord(): AbstractRecord
    {
        $coordinates = new LocationVOCollection;

        foreach ($this->input('coordinates') as $pair) {
            $coordinates->add(LocationVO::fromArray([(float) $pair[0], (float) $pair[1]]));
        }

        $annotations = null;
        $rawAnnotations = $this->input('annotations');

        if (is_array($rawAnnotations)) {
            $annotations = new MatrixAnnotationCollection;

            foreach ($rawAnnotations as $annotation) {
                $annotations->add(MatrixAnnotation::from($annotation));
            }
        }

        $sources = null;
        $rawSources = $this->input('sources');

        if (is_array($rawSources)) {
            $sources = new IntTypedCollection;

            foreach ($rawSources as $index) {
                $sources->add((int) $index);
            }
        }

        $destinations = null;
        $rawDestinations = $this->input('destinations');

        if (is_array($rawDestinations)) {
            $destinations = new IntTypedCollection;

            foreach ($rawDestinations as $index) {
                $destinations->add((int) $index);
            }
        }

        $fallbackCoordinate = $this->input('fallback_coordinate');

        $options = MatrixOptionsVO::create(
            coordinates: $coordinates,
            annotations: $annotations,
            sources: $sources,
            destinations: $destinations,
            fallbackSpeed: $this->input('fallback_speed') !== null
                ? (float) $this->input('fallback_speed')
                : null,
            fallbackCoordinate: $fallbackCoordinate !== null
                ? FallbackCoordinate::from($fallbackCoordinate)
                : null,
        );

        return new MatrixRecord(
            options: $options,
            profile: DirectionsProfile::from($this->input('profile') ?? 'driving'),
        );
    }
}
