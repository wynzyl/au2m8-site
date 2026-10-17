@extends('layouts.app')

@section('title', 'Contact - ' . $company['name'])

@section('content')
<div class="max-w-4xl mx-auto px-6 py-20">
    <section class="mb-16">
        <h1 class="text-4xl font-semibold tracking-tight mb-4">
            Get in Touch
        </h1>
        <p class="text-lg text-gray-500">
            We'd love to hear about your needs.
        </p>
    </section>

    <section>
        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-8">
            Contact Information
        </h2>
        <div class="grid gap-6">
            <div>
                <p class="text-sm text-gray-400 mb-1">Email</p>
                <p class="font-medium">{{ $company['email'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-400 mb-1">Phone</p>
                <p class="font-medium">{{ $company['phone'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-400 mb-1">Address</p>
                <p class="font-medium">{{ $company['address'] }}</p>
            </div>
        </div>
    </section>
</div>
@endsection
