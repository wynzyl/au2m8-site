@extends('layouts.app')

@section('title', 'Sign in - ' . $company->name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="max-w-sm mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <h1 class="text-3xl font-semibold tracking-tight mb-8">Sign in</h1>

    @if (session('status'))
        <p role="status" class="mb-6 rounded-lg bg-orange-50 px-4 py-3 text-sm text-orange-900">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="grid gap-6">
        @csrf

        <x-form.field
            name="email"
            label="Email"
            type="email"
            autocomplete="username"
            required
            autofocus
        />

        <x-form.field
            name="password"
            label="Password"
            type="password"
            autocomplete="current-password"
            required
        />

        <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="remember" value="1" class="h-5 w-5 rounded border-gray-300 text-orange-700 focus:ring-orange-700">
            Remember me
        </label>

        <button type="submit" class="btn-primary w-full min-h-11">Sign in</button>
    </form>
</div>
@endsection
