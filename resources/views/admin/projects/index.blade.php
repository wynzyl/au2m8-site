@extends('layouts.app')

@section('title', 'Projects - ' . $company->name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <x-admin.nav />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">Projects</h1>
        <a href="{{ route('admin.projects.create') }}" class="btn-primary min-h-11">Add project</a>
    </div>

    @if (session('status'))
        <p role="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </p>
    @endif

    @if ($projects->isEmpty())
        <p>No projects yet.</p>
    @else
        <ul class="divide-y divide-gray-200">
            @foreach ($projects as $project)
                <li class="flex flex-wrap items-center gap-4 py-4">
                    @if ($imageUrl = $project->imageUrl())
                        <img
                            src="{{ $imageUrl }}"
                            alt=""
                            width="96"
                            height="64"
                            @unless ($loop->first) loading="lazy" @endunless
                            class="h-16 w-24 rounded object-cover"
                        >
                    @else
                        <div aria-hidden="true" class="h-16 w-24 rounded bg-gray-100"></div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="font-medium wrap-break-word">{{ $project->title }}</p>
                        <p class="text-sm text-gray-600">
                            {{ $project->is_published ? 'Published' : 'Hidden' }} · Order {{ $project->sort_order }}
                        </p>
                    </div>

                    <div class="flex w-full gap-2 sm:w-auto">
                        <a
                            href="{{ route('admin.projects.edit', $project) }}"
                            aria-label="Edit {{ $project->title }}"
                            class="btn-secondary min-h-11"
                        >Edit</a>
                        <a
                            href="{{ route('admin.projects.delete', $project) }}"
                            aria-label="Delete {{ $project->title }}"
                            class="btn-secondary min-h-11"
                        >Delete</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
