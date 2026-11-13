@props([
    'icon',
    'iconClass' => 'bg-brand-soft text-brand',
    'label',
    'value',
    'href' => null,
    'linkLabel' => null,
])

<x-card {{ $attributes->class('flex flex-col gap-2.5') }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-[14px] {{ $iconClass }}">
        <x-icon :name="$icon" class="h-[22px] w-[22px]" />
    </span>
    <span class="text-sm font-semibold text-muted">{{ $label }}</span>
    <span class="text-5xl font-extrabold leading-none text-ink">{{ $value }}</span>
    @if ($href && $linkLabel)
        <a href="{{ $href }}" class="mt-1.5 inline-flex items-center gap-1 text-sm font-bold text-brand hover:underline">
            {{ $linkLabel }} <x-icon name="arrow-right" class="h-4 w-4" />
        </a>
    @endif
</x-card>
