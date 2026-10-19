<?php

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * @return array<string, int|string>
 */
function validCompanyProfileInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Au2m8',
        'tagline' => 'Building the web, streamlining the process.',
        'description' => 'We craft modern, performant web applications using the latest technologies.',
        'founded_year' => 2010,
        'mission' => 'To deliver exceptional web solutions that help businesses thrive in the digital age.',
        'email' => 'wenzel@au2m8.com',
        'phone' => '+63 (900) 000-0000',
        'address' => 'Poblacion, Urdaneta City, Pangasinan, 2428 Philippines',
    ], $overrides);
}

test('guest cannot view the company details screen', function () {
    $this->get(route('admin.company.edit'))->assertRedirect(route('login'));
});

test('guest cannot update the company details', function () {
    $this->put(route('admin.company.update'), validCompanyProfileInput())
        ->assertRedirect(route('login'));

    expect(CompanyProfile::query()->first()->phone)->toBe('+63 (919) 095-9251');
});

test('owner sees the form filled with the current details', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.company.edit'))
        ->assertOk()
        ->assertSee('value="wenzel@au2m8.com"', false)
        ->assertSee('value="+63 (919) 095-9251"', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('owner update is saved and shown on public pages immediately', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.company.update'), validCompanyProfileInput())
        ->assertRedirect(route('admin.company.edit'))
        ->assertSessionHas('status', 'Company details saved.');

    expect(CompanyProfile::query()->first()->phone)->toBe('+63 (900) 000-0000');

    $this->get(route('contact'))
        ->assertSee('+63 (900) 000-0000')
        ->assertDontSee('+63 (919) 095-9251');
});

test('saving without changes succeeds', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.company.update'), validCompanyProfileInput(['phone' => '+63 (919) 095-9251']))
        ->assertRedirect(route('admin.company.edit'))
        ->assertSessionHasNoErrors();
});

test('invalid details are rejected and nothing changes', function (string $field, mixed $value) {
    $this->actingAs(User::factory()->create())
        ->from(route('admin.company.edit'))
        ->put(route('admin.company.update'), validCompanyProfileInput([$field => $value]))
        ->assertRedirect(route('admin.company.edit'))
        ->assertSessionHasErrors($field);

    expect(CompanyProfile::query()->first()->only(['name', 'phone']))
        ->toBe(['name' => 'Au2m8', 'phone' => '+63 (919) 095-9251']);
})->with([
    'blank name' => ['name', ''],
    'blank tagline' => ['tagline', ''],
    'blank description' => ['description', ''],
    'blank founded year' => ['founded_year', ''],
    'blank mission' => ['mission', ''],
    'blank email' => ['email', ''],
    'blank phone' => ['phone', ''],
    'blank address' => ['address', ''],
    'name too long' => ['name', str_repeat('a', 101)],
    'tagline too long' => ['tagline', str_repeat('a', 151)],
    'description too long' => ['description', str_repeat('a', 501)],
    'mission too long' => ['mission', str_repeat('a', 501)],
    'address too long' => ['address', str_repeat('a', 501)],
    'malformed email' => ['email', 'not-an-email'],
    'phone with letters' => ['phone', 'call me'],
    'founded before 1900' => ['founded_year', 1899],
    'founded in the future' => ['founded_year', (int) date('Y') + 1],
    'founded not a number' => ['founded_year', 'abc'],
]);

test('a failed save keeps the other values the owner typed', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('admin.company.edit'))
        ->put(route('admin.company.update'), validCompanyProfileInput([
            'name' => '',
            'tagline' => 'A brand new tagline',
            'address' => 'Somewhere new, Pangasinan',
        ]))
        ->assertRedirect(route('admin.company.edit'));

    $this->get(route('admin.company.edit'))
        ->assertOk()
        ->assertSee('value="A brand new tagline"', false)
        ->assertSee('value="+63 (900) 000-0000"', false)
        ->assertSee('Somewhere new, Pangasinan')
        ->assertSee('The name field is required.');
});

test('saving creates the record when it is missing', function () {
    CompanyProfile::query()->delete();
    Cache::flush();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.company.update'), validCompanyProfileInput())
        ->assertRedirect(route('admin.company.edit'));

    expect(CompanyProfile::query()->count())->toBe(1);
});
