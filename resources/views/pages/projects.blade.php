@extends('layouts.app')

@section('title', 'Projects - Au2m8')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-20">
    <section class="mb-12">
        <h1 class="text-4xl font-semibold tracking-tight mb-4">
            Our Projects
        </h1>
        <p class="text-lg text-gray-500">
            A selection of work we're proud of.
        </p>
    </section>

    <section class="grid gap-8 md:grid-cols-2">
        @foreach ($projects as $project)
            <div class="group">
                <div class="aspect-[3/2] overflow-hidden rounded-lg mb-4">
                    <img
                        src="{{ $project['image'] }}"
                        alt="{{ $project['title'] }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    >
                </div>
                <h3 class="font-medium mb-2">{{ $project['title'] }}</h3>
                <p class="text-sm text-gray-500 mb-3">{{ $project['description'] }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($project['tags'] as $tag)
                        <span class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</div>
@endsection
