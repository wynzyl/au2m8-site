<?php

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('public page has a collapsible mobile menu with every link', function (string $routeName) {
    $content = $this->get(route($routeName))->assertOk()->getContent();

    $mobileMenu = Str::between($content, '<details', '</details>');

    expect($content)->toMatch('/<details[^>]*class="[^"]*sm:hidden/')
        ->and($content)->toMatch('/class="[^"]*hidden sm:flex/')
        ->and($mobileMenu)->toContain('aria-label="Menu"');

    foreach (['home', 'about', 'projects', 'contact'] as $linkedRoute) {
        expect($mobileMenu)->toContain('href="'.route($linkedRoute).'"');
    }
})->with(['home', 'about', 'projects', 'contact']);

test('active page is marked as current in the navigation', function () {
    $content = $this->get(route('about'))->getContent();

    expect(substr_count($content, 'aria-current="page"'))->toBe(2);
});

test('contact page offers tap to email and tap to call links', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('href="mailto:wenzel@au2m8.com"', false)
        ->assertSee('href="tel:+639190959251"', false)
        ->assertSee('wrap-break-word', false);
});

test('contact page omits contact links when the profile record is missing', function () {
    CompanyProfile::query()->delete();
    Cache::flush();

    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('href="mailto:', false)
        ->assertDontSee('href="tel:', false);
});

test('project images are sized and lazy loaded below the fold', function () {
    Storage::fake('public');
    Storage::disk('public')->put('projects/merryland.webp', 'x');
    Storage::disk('public')->put('projects/healthcare-dashboard.webp', 'x');

    preg_match_all('/<img[^>]*>/', $this->get(route('projects'))->getContent(), $matches);
    $images = $matches[0];
    $titles = ['School Management System', 'Healthcare Management System'];

    expect($images)->toHaveCount(2);

    foreach ($images as $index => $image) {
        expect($image)->toContain('alt="'.$titles[$index].'"')
            ->toContain('decoding="async"')
            ->toContain('width="816"')
            ->toContain('height="544"');

        if ($index === 0) {
            expect($image)->not->toContain('loading="lazy"');
        } else {
            expect($image)->toContain('loading="lazy"');
        }
    }
});

test('page content avoids low contrast grey text on white', function (string $routeName) {
    $main = Str::between($this->get(route($routeName))->getContent(), '<main', '</main>');

    expect($main)->not->toContain('text-gray-400');
})->with(['home', 'about', 'contact']);
