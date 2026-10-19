@extends('layouts.app')

@section('title', 'Projects - ' . $company->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <section class="mb-12">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight mb-4">
            Our Projects
        </h1>
        <p class="text-base sm:text-lg text-gray-500">
            A selection of work we're proud of.
        </p>
    </section>

    <section class="grid gap-8 md:grid-cols-2">
        @forelse ($projects as $project)
            <div class="group">
                @if ($imageUrl = $project->imageUrl())
                    <div class="aspect-[3/2] overflow-hidden rounded-lg mb-4">
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ $project->title }}"
                            width="816"
                            height="544"
                            decoding="async"
                            @unless ($loop->first) loading="lazy" @endunless
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        >
                    </div>
                @else
                    <div data-image-placeholder aria-hidden="true" class="aspect-3/2 rounded-lg mb-4 bg-gray-100"></div>
                @endif
                <h2 class="font-medium mb-2 wrap-break-word">{{ $project->title }}</h2>
                <p class="text-sm text-gray-500 mb-3 wrap-break-word">{{ $project->description }}</p>
                @if (! empty($project->tags))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($project->tags as $tag)
                            <span class="text-xs text-orange-800 bg-orange-50 px-2 py-1 rounded wrap-break-word">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="text-base text-gray-600">New projects will be added soon.</p>
        @endforelse
    </section>
</div>
@endsection
