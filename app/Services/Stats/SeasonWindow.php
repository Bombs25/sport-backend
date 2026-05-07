<?php

namespace App\Services\Stats;

use Carbon\CarbonImmutable;

final class SeasonWindow
{
    public function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $startDate,
        public readonly CarbonImmutable $endDate,
    ) {}
}
