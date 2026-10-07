<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Http\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Traits\Hydratable;

final class BalanceRecord extends AbstractRecord
{
    use Hydratable;

    public function __construct() {}
}
