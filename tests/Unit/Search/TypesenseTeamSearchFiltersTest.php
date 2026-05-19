<?php

namespace Tests\Unit\Search;

use App\Services\Search\TypesenseTeamService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypesenseTeamSearchFiltersTest extends TestCase
{
    #[Test]
    public function build_search_exclusion_filters_includes_creator_and_team_ids(): void
    {
        $filters = TypesenseTeamService::buildSearchExclusionFilters(7, [3, 12, 3, 0]);

        $this->assertSame([
            'creator_id:!=7',
            'id:!=[3, 12]',
        ], $filters);
    }

    #[Test]
    public function build_search_exclusion_filters_omits_empty_team_ids(): void
    {
        $filters = TypesenseTeamService::buildSearchExclusionFilters(42, []);

        $this->assertSame(['creator_id:!=42'], $filters);
    }

    #[Test]
    public function build_search_exclusion_filters_returns_empty_when_no_exclusions(): void
    {
        $this->assertSame([], TypesenseTeamService::buildSearchExclusionFilters(null, []));
    }
}
