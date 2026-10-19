@extends('layouts.app')

@section('title', ($project->exists ? 'Edit project - ' : 'Add project - ') . $company->name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@php
    $imageHasError = $errors->has('image');
    $imageDescribedBy = 'field-image-hint'.($imageHasError ? ' field-image-error' : '');
    $tagsHint = 'Separate tags with commas.'
        .($tagSuggestions === [] ? '' : ' Tags already in use: '.implode(', ', $tagSuggestions).'.');
    $isPublished = $errors->any() ? (bool) old('is_published') : $project->is_published;
@endphp

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <x-admin.nav />

    <div class="sm:max-w-2xl">
        <h1 class="text-3xl font-semibold tracking-tight mb-8">{{ $project->exists ? 'Edit project' : 'Add project' }}</h1>

        <form
            method="POST"
            action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf
            @if ($project->exists)
                @method('PUT')
            @endif

            <x-form.field name="title" label="Title" :value="$project->title" maxlength="100" required />
            <x-form.field name="description" label="Description" :value="$project->description" maxlength="500" multiline required />

            <div>
                <label for="field-image" class="block text-sm font-medium text-gray-700 mb-1">Image</label>

                @if ($project->exists && ($currentImageUrl = $project->imageUrl()))
                    <img src="{{ $currentImageUrl }}" alt="Current image" width="192" height="128" class="mb-3 h-32 w-48 rounded object-cover">
                @endif

                <input
                    id="field-image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @unless ($project->exists) required @endunless
                    @if ($imageHasError) aria-invalid="true" @endif
                    aria-describedby="{{ $imageDescribedBy }}"
                    class="block w-full min-h-11 rounded-lg border bg-white px-3 py-2 text-base text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-700 focus:border-orange-700 {{ $imageHasError ? 'border-red-700' : 'border-gray-300' }}"
                >

                <p id="field-image-hint" class="mt-1 text-sm text-gray-600">
                    JPEG, PNG or WebP, up to 2 MB. Large images are scaled down automatically.@if ($project->exists) Leave empty to keep the current image.@endif
                </p>

                @error('image')
                    <p id="field-image-error" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <x-form.field
                name="tags"
                label="Tags"
                :value="implode(', ', $project->tags ?? [])"
                list="tag-suggestions"
                autocomplete="off"
                :data-tag-suggestions="json_encode($tagSuggestions)"
                :hint="$tagsHint"
            />

            <datalist id="tag-suggestions">
                @foreach ($tagSuggestions as $tag)
                    <option value="{{ $tag }}"></option>
                @endforeach
            </datalist>

            <div>
                <label for="field-is_published" class="inline-flex min-h-11 items-center gap-3 text-sm font-medium text-gray-700">
                    <input
                        type="checkbox"
                        name="is_published"
                        id="field-is_published"
                        value="1"
                        @checked($isPublished)
                        class="h-5 w-5 rounded border-gray-300 text-orange-700 focus:ring-2 focus:ring-orange-700"
                    >
                    Published (shown on the Projects page)
                </label>
            </div>

            <x-form.field
                name="sort_order"
                label="Display order"
                type="number"
                :value="$project->sort_order"
                min="0"
                max="999"
                inputmode="numeric"
                required
                hint="Lower numbers appear first."
            />

            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="submit" class="btn-primary min-h-11">{{ $project->exists ? 'Save changes' : 'Add project' }}</button>
                <a href="{{ route('admin.projects.index') }}" class="btn-secondary min-h-11">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
