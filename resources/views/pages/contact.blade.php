@extends('layouts.app')

@section('title', 'Contact - ' . $company->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <section class="mb-12 sm:mb-16">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight mb-4">
            Get in Touch
        </h1>
        <p class="text-base sm:text-lg text-gray-500">
            We'd love to hear about your needs.
        </p>
    </section>

    <section>
        <h2 class="text-sm font-medium text-gray-600 uppercase tracking-wider mb-8">
            Contact Information
        </h2>
        <div class="grid gap-6">
            @if ($company->email)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Email</p>
                    <a href="mailto:{{ $company->email }}" class="inline-block max-w-full py-2.5 font-medium text-accent wrap-break-word">{{ $company->email }}</a>
                </div>
            @endif
            @if ($company->phone)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Phone</p>
                    <a href="{{ $company->telephoneHref() }}" class="inline-block max-w-full py-2.5 font-medium text-accent">{{ $company->phone }}</a>
                </div>
            @endif
            @if ($company->address)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Address</p>
                    <p class="font-medium wrap-break-word">{{ $company->address }}</p>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
