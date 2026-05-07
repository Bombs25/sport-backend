<?php

namespace App\Services\Stats;

use App\Contracts\Stats\SeasonStrategy;
use Carbon\CarbonImmutable;

class AnnualSeasonStrategy implements SeasonStrategy
{
    public function resolveWindowForDate(CarbonImmutable $referenceDate): SeasonWindow
    {
        $year = $referenceDate->year;

        return new SeasonWindow(
            key: (string) $year,
            startDate: CarbonImmutable::create($year, 1, 1, 0, 0, 0),
            endDate: CarbonImmutable::create($year, 12, 31, 23, 59, 59),
        );
    }
}
