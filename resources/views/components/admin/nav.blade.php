@php
    $links = [
        ['label' => 'Company details', 'href' => route('admin.company.edit'), 'active' => request()->routeIs('admin.company.*')],
        ['label' => 'Projects', 'href' => route('admin.projects.index'), 'active' => request()->routeIs('admin.projects.*')],
    ];
@endphp

<nav aria-label="Admin" class="mb-8 flex flex-wrap items-center gap-2 border-b border-gray-200 pb-4">
    @foreach ($links as $link)
        <a
            href="{{ $link['href'] }}"
            @if ($link['active']) aria-current="page" @endif
            class="inline-flex min-h-11 items-center px-3 rounded-md text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700 {{ $link['active'] ? 'font-medium text-gray-900' : 'text-gray-600 hover:text-gray-900' }}"
        >
            {{ $link['label'] }}
        </a>
    @endforeach

    <form method="POST" action="{{ route('logout') }}" class="sm:ml-auto">
        @csrf
        <button type="submit" class="btn-secondary min-h-11">Sign out</button>
    </form>
</nav>
