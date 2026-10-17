@extends('layouts.app')

@section('title', 'About - ' . $company['name'])

@section('content')
<div class="max-w-4xl mx-auto px-6 py-20">
    <section class="mb-16">
        <h1 class="text-4xl font-semibold tracking-tight mb-4">
            About {{ $company['name'] }}
        </h1>
        <p class="text-lg text-gray-500">
            Founded in {{ $company['founded'] }}. {{ $company['mission'] }}
        </p>
    </section>

    <section>
        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-8">
            Our Team
        </h2>
        <div class="grid gap-6">
            @foreach ($team as $member)
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <span class="text-gray-500 font-medium">
                            {{ substr($member['name'], 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium">{{ $member['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $member['role'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
