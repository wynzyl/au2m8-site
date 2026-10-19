<?php

use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

test('login page renders and is hidden from search engines', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('owner can sign in with valid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.company.edit'));

    $this->assertAuthenticatedAs($user);
});

test('wrong password keeps the visitor signed out', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email', $user->email);

    $this->assertGuest();
});

test('sign in is throttled after five failed attempts', function () {
    $this->freezeTime();
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 60])]);

    $this->assertGuest();
});

test('signed in owner visiting login is redirected to the admin screen', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('admin.company.edit'));
});

test('owner can sign out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('guest cannot reach sign out', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

test('there is no public sign up', function () {
    $this->get('/register')->assertNotFound();
});

test('expired session on an admin form redirects to sign in with a message', function (string $method, string $uri) {
    Route::middleware('web')->match([$method], $uri, fn () => throw new TokenMismatchException);

    $this->call($method, $uri)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your session expired. Please sign in again.');
})->with([
    'admin form' => ['PUT', '/admin/_expired-probe'],
    'sign in form' => ['POST', '/login/_expired-probe'],
]);
