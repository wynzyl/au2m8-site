@props([
    'name',
    'label',
    'value' => null,
    'type' => 'text',
    'multiline' => false,
])

@php
    $inputId = 'field-'.$name;
    $errorId = $inputId.'-error';
    $hasError = $errors->has($name);
    $fieldClasses = 'block w-full min-h-11 rounded-lg border bg-white px-3 py-2 text-base text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-700 focus:border-orange-700 '
        .($hasError ? 'border-red-700' : 'border-gray-300');
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

    @if ($multiline)
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            rows="3"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
            {{ $attributes->merge(['class' => $fieldClasses]) }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
            {{ $attributes->merge(['class' => $fieldClasses]) }}
        >
    @endif

    @error($name)
        <p id="{{ $errorId }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
