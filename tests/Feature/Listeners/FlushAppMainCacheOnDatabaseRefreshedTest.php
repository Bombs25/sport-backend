<?php

namespace Tests\Feature\Listeners;

use App\Listeners\FlushAppMainCacheOnDatabaseRefreshed;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FlushAppMainCacheOnDatabaseRefreshedTest extends TestCase
{
    public function test_it_flushes_the_app_main_cache_store(): void
    {
        $store = Mockery::mock(Repository::class);
        $store->shouldReceive('flush')->once();

        Cache::shouldReceive('store')
            ->once()
            ->with('app_main_cache')
            ->andReturn($store);

        (new FlushAppMainCacheOnDatabaseRefreshed)->handle(new DatabaseRefreshed);
    }
}
