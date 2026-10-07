<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\PhpLocationIq\Records\TimezoneRecord;
use AndyDefer\PhpVo\ValueObjects\CoordinatesVO;
use AndyDefer\PhpVo\ValueObjects\Types\FloatVO;

final class TimezoneRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'timestamp' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        return new TimezoneRecord(
            coordinates: new CoordinatesVO(
                FloatVO::from((float) $this->input('lat')),
                FloatVO::from((float) $this->input('lon')),
            ),
            timestamp: $this->input('timestamp') !== null
                ? (int) $this->input('timestamp')
                : null,
        );
    }
}
