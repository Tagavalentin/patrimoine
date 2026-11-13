@props([
    'variant' => 'primary', // primary | outline | neutral | gold
    'href' => null,
])

@php
$variantClass = [
    'primary' => 'bg-brand text-white hover:bg-brand-dark',
    'outline' => 'border-[1.5px] border-brand text-brand hover:bg-brand-soft',
    'neutral' => 'border-[1.5px] border-line-strong text-ink hover:bg-page',
    'gold' => 'bg-gold text-sidebar hover:brightness-95',
][$variant] ?? 'bg-brand text-white';

$tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @else type="{{ $attributes->get('type', 'button') }}" @endif
    {{ $attributes->merge(['class' => "inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold transition-colors {$variantClass}"]) }}>
    {{ $slot }}
</{{ $tag }}>
