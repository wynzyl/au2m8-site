<?php

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('current returns the migrated company profile', function () {
    $profile = CompanyProfile::current();

    expect($profile->exists)->toBeTrue()
        ->and($profile->name)->toBe('Au2m8')
        ->and($profile->email)->toBe('wenzel@au2m8.com');
});

test('current is served from the cache after the first read', function () {
    CompanyProfile::current();

    DB::enableQueryLog();
    CompanyProfile::current();

    $profileQueries = collect(DB::getQueryLog())
        ->filter(fn (array $entry): bool => str_contains($entry['query'], 'company_profiles'));

    expect($profileQueries)->toBeEmpty();
});

test('current survives a round trip through a serializing cache store', function () {
    Cache::setDefaultDriver('database');

    CompanyProfile::current();
    $profile = CompanyProfile::current();

    expect($profile)->toBeInstanceOf(CompanyProfile::class)
        ->and($profile->exists)->toBeTrue()
        ->and($profile->name)->toBe('Au2m8')
        ->and($profile->founded_year)->toBe(2010);
});

test('saving the profile clears the cache so the new value is read', function () {
    CompanyProfile::current()->update(['phone' => '+63 (900) 000-0000']);

    expect(CompanyProfile::current()->phone)->toBe('+63 (900) 000-0000');
});

test('current falls back to the application name when no record exists', function () {
    CompanyProfile::query()->delete();
    Cache::flush();

    $profile = CompanyProfile::current();

    expect($profile->exists)->toBeFalse()
        ->and($profile->name)->toBe(config('app.name'))
        ->and(Cache::has(CompanyProfile::CACHE_KEY))->toBeFalse();
});

test('telephone href strips everything except digits and plus', function () {
    expect(CompanyProfile::current()->telephoneHref())->toBe('tel:+639190959251');
});

test('deleting the profile clears the cache', function () {
    $profile = CompanyProfile::current();
    expect(Cache::has(CompanyProfile::CACHE_KEY))->toBeTrue();

    $profile->delete();

    expect(Cache::has(CompanyProfile::CACHE_KEY))->toBeFalse();
});
