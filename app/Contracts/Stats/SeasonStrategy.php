<?php

namespace App\Contracts\Stats;

use App\Services\Stats\SeasonWindow;
use Carbon\CarbonImmutable;

interface SeasonStrategy
{
    public function resolveWindowForDate(CarbonImmutable $referenceDate): SeasonWindow;
}
