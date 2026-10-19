<?php

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;

test('public page shows the company name in the title, header and footer', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertOk()
        ->assertSee('<title>', false)
        ->assertSee(date('Y').' Au2m8. All rights reserved.');

    preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $title);
    expect($title[1])->toContain('Au2m8')
        ->and(substr_count($response->getContent(), 'Au2m8'))->toBeGreaterThanOrEqual(4);
})->with(['home', 'about', 'projects', 'contact']);

test('home page shows the tagline and description', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Building the web, streamlining the process.')
        ->assertSee('We craft modern, performant web applications using the latest technologies.');
});

test('about page shows the name, founding year and mission', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('About Au2m8')
        ->assertSee('Founded in 2010.')
        ->assertSee('To deliver exceptional web solutions that help businesses thrive in the digital age.');
});

test('projects page title uses the company name', function () {
    $this->get(route('projects'))
        ->assertOk()
        ->assertSee('<title>Projects - Au2m8</title>', false);
});

test('contact page shows the email, phone and address', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('wenzel@au2m8.com')
        ->assertSee('+63 (919) 095-9251')
        ->assertSee('Poblacion, Urdaneta City, Pangasinan, 2428 Philippines');
});

test('company name is escaped and replaces the old name everywhere', function () {
    CompanyProfile::query()->first()->update(['name' => 'Acme & <Co>']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Acme & <Co>')
        ->assertSee('Acme &amp; &lt;Co&gt;', false)
        ->assertDontSee('Au2m8');
});

test('public page still renders when the profile record is missing', function (string $routeName) {
    CompanyProfile::query()->delete();
    Cache::flush();

    $this->get(route($routeName))
        ->assertOk()
        ->assertSee(config('app.name'));
})->with(['home', 'about', 'projects', 'contact']);
