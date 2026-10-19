@extends('layouts.app')

@section('title', 'Company details - ' . $company->name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <div class="sm:max-w-2xl">
        <x-admin.nav />

        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight">Company details</h1>
        </div>

        @if (session('status'))
            <p role="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('admin.company.update') }}" class="grid gap-6">
            @csrf
            @method('PUT')

            <x-form.field name="name" label="Company name" :value="$profile->name" maxlength="100" required />
            <x-form.field name="tagline" label="Tagline" :value="$profile->tagline" maxlength="150" required />
            <x-form.field name="description" label="Description" :value="$profile->description" maxlength="500" multiline required />
            <x-form.field
                name="founded_year"
                label="Founded (year)"
                type="number"
                :value="$profile->founded_year"
                min="1900"
                :max="now()->year"
                inputmode="numeric"
                required
            />
            <x-form.field name="mission" label="Mission" :value="$profile->mission" maxlength="500" multiline required />
            <x-form.field name="email" label="Contact email" type="email" :value="$profile->email" maxlength="255" required />
            <x-form.field name="phone" label="Contact phone" type="tel" :value="$profile->phone" maxlength="30" required />
            <x-form.field name="address" label="Address" :value="$profile->address" maxlength="500" multiline required />

            <div>
                <button type="submit" class="btn-primary w-full sm:w-auto min-h-11">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
