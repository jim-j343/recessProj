<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    /**
     * The legacy {id}-based group routes bypass route-model binding and
     * render group pages with an empty Group model. They were removed on
     * 24 Jul 2026 and resurrected once by a merge — this test makes any
     * future resurrection fail CI instead of crashing production.
     */
    public function test_no_legacy_id_based_group_routes_exist(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString(
                'groups/{id}',
                $route->uri(),
                "Legacy route [{$route->uri()}] has been reintroduced — remove the old group route block from routes/web.php."
            );
        }
    }

    public function test_group_route_names_are_defined_exactly_once(): void
    {
        $names = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter();
        $duplicates = $names->duplicates()->values()->all();
        $this->assertSame([], $duplicates, 'Duplicate route names found: '.implode(', ', $duplicates));
    }
}
