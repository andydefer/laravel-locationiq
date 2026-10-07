<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\PhpLocationIq\Enums\NominatimFormat;
use AndyDefer\PhpLocationIq\Records\Nominatim\ReverseRecord;
use AndyDefer\PhpVo\ValueObjects\CoordinatesVO;
use AndyDefer\PhpVo\ValueObjects\Types\FloatVO;
use Illuminate\Validation\Rule;

final class ReverseRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'format' => ['nullable', Rule::in(['json', 'jsonv2', 'geojson', 'geocodejson'])],
            'accept_language' => ['nullable', 'string', 'max:10'],
            'zoom' => ['nullable', 'integer', 'between:0,18'],
            'address_details' => ['nullable', 'boolean'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $format = $this->input('format');

        return new ReverseRecord(
            coordinates: new CoordinatesVO(
                FloatVO::from((float) $this->input('lat')),
                FloatVO::from((float) $this->input('lon')),
            ),
            format: $format !== null
                ? NominatimFormat::from($format)
                : NominatimFormat::default(),
            acceptLanguage: $this->input('accept_language'),
            zoom: $this->input('zoom') !== null ? (int) $this->input('zoom') : null,
            addressDetails: $this->input('address_details') !== null
                ? (bool) $this->input('address_details')
                : null,
        );
    }
}
