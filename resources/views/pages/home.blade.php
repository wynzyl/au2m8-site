@extends('layouts.app')

@section('title', $company->name . ' - Web Development')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <section class="text-center mb-12 sm:mb-20">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-balance mb-4">
            {{ $company->tagline ?: $company->name }}
        </h1>
        @if ($company->description)
            <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">
                {{ $company->description }}
            </p>
        @endif
    </section>

    <section>
        <h2 class="text-sm font-medium text-gray-600 uppercase tracking-wider mb-8">
            Our Services
        </h2>
        <div class="grid gap-8">
            @foreach ($services as $service)
                <div class="border-l-2 border-orange-500 pl-6">
                    <h3 class="font-medium mb-1">{{ $service['name'] }}</h3>
                    <p class="text-gray-500">{{ $service['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
