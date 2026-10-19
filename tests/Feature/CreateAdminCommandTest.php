<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('creates a site owner with a hashed password', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Name', 'Site Owner')
        ->expectsQuestion('Email', 'owner@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'owner@example.com')->sole();

    expect(Hash::check('correct-horse-battery', $user->password))->toBeTrue();
});

test('rejects invalid owner details', function (string $email, string $password, string $confirmation) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('app:create-admin')
        ->expectsQuestion('Name', 'Site Owner')
        ->expectsQuestion('Email', $email)
        ->expectsQuestion('Password', $password)
        ->expectsQuestion('Confirm password', $confirmation)
        ->assertExitCode(1);

    expect(User::query()->count())->toBe(1);
})->with([
    'duplicate email' => ['taken@example.com', 'correct-horse-battery', 'correct-horse-battery'],
    'short password' => ['owner@example.com', 'eleven-char', 'eleven-char'],
    'mismatched confirmation' => ['owner@example.com', 'correct-horse-battery', 'correct-horse-staple'],
]);
