@extends('layouts.app')

@section('title', 'Delete project - ' . $company->name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
    <x-admin.nav />

    <h1 class="text-3xl font-semibold tracking-tight mb-8">Delete project</h1>

    <p class="mb-6 wrap-break-word">Delete “{{ $project->title }}”? This cannot be undone.</p>

    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="flex flex-col gap-3 sm:flex-row">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-primary min-h-11">Delete project</button>
        <a href="{{ route('admin.projects.index') }}" class="btn-secondary min-h-11">Cancel</a>
    </form>
</div>
@endsection
