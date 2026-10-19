<?php

use App\Models\CompanyProfile;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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

/**
 * Put both seeded project images on the fake public disk.
 */
function fakeSeededProjectImages(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('projects/merryland.webp', 'x');
    Storage::disk('public')->put('projects/healthcare-dashboard.webp', 'x');
}

test('projects page shows the stored projects in order with their images and tags', function () {
    fakeSeededProjectImages();

    $response = $this->get(route('projects'))
        ->assertOk()
        ->assertSeeInOrder(['School Management System', 'Healthcare Management System'])
        ->assertSee('Complete student information system with enrollment, grades, billing, and reporting.')
        ->assertSee('Real-time patient monitoring system with analytics and reporting.');

    foreach (['NextJS', 'React', 'PostgreSQL', 'Laravel', 'Livewire'] as $tag) {
        $response->assertSee($tag);
    }

    expect($response->getContent())
        ->toMatch('/<img[^>]*src="[^"]*\/storage\/projects\/merryland\.webp"/')
        ->toMatch('/<img[^>]*src="[^"]*\/storage\/projects\/healthcare-dashboard\.webp"/');
});

test('projects page leaves out hidden projects', function () {
    fakeSeededProjectImages();
    Project::factory()->unpublished()->create(['title' => 'Secret Build']);

    $this->get(route('projects'))
        ->assertOk()
        ->assertDontSee('Secret Build');
});

test('projects page shows a message when nothing is published', function () {
    fakeSeededProjectImages();
    Project::query()->update(['is_published' => false]);

    $this->get(route('projects'))
        ->assertOk()
        ->assertSee('Our Projects')
        ->assertSee('New projects will be added soon.')
        ->assertDontSee('<img', false);
});

test('project titles are escaped', function () {
    fakeSeededProjectImages();
    Project::factory()->create(['title' => 'R&D <Lab>']);

    $this->get(route('projects'))
        ->assertOk()
        ->assertSee('R&amp;D &lt;Lab&gt;', false);
});

test('a project with a missing image shows a placeholder', function () {
    fakeSeededProjectImages();
    Storage::disk('public')->delete('projects/healthcare-dashboard.webp');

    $content = $this->get(route('projects'))->assertOk()->getContent();

    expect(substr_count($content, '<img'))->toBe(1)
        ->and($content)->toContain('data-image-placeholder');
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
