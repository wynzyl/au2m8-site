<?php

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Get the admin navigation markup from the given admin screen.
 */
function adminNavigation(string $routeName): string
{
    $content = test()->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertOk()
        ->getContent();

    return '<nav aria-label="Admin"'.Str::between($content, '<nav aria-label="Admin"', '</nav>').'</nav>';
}

test('the company screen links to both admin sections and signs out', function () {
    $nav = adminNavigation('admin.company.edit');

    expect($nav)->toContain('href="'.route('admin.company.edit').'"')
        ->toContain('href="'.route('admin.projects.index').'"')
        ->toContain('action="'.route('logout').'"')
        ->toMatch('/<a[^>]*href="'.preg_quote(route('admin.company.edit'), '/').'"[^>]*aria-current="page"/')
        ->and(substr_count($nav, 'aria-current="page"'))->toBe(1);
});

test('the projects link is current on every project screen', function (string $routeName) {
    $nav = adminNavigation($routeName);

    expect($nav)->toMatch('/<a[^>]*href="'.preg_quote(route('admin.projects.index'), '/').'"[^>]*aria-current="page"/')
        ->and(substr_count($nav, 'aria-current="page"'))->toBe(1);
})->with(['admin.projects.index', 'admin.projects.create']);
