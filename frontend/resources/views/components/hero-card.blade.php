@props([
    'label',
    'value',
    'generatedAt' => null,
    'chips' => [],
])

<div {{ $attributes->merge(['class' => 'relative flex min-h-[260px] flex-col gap-3.5 overflow-hidden rounded-3xl bg-brand p-8 text-white']) }}>
    {{-- Cercles décoratifs --}}
    <svg class="absolute -right-[60px] -top-[70px] h-80 w-80" viewBox="0 0 320 320" fill="none" stroke="#1B8E76" stroke-width="2" aria-hidden="true">
        <circle cx="160" cy="160" r="60"></circle>
        <circle cx="160" cy="160" r="100"></circle>
        <circle cx="160" cy="160" r="140"></circle>
    </svg>

    <span class="relative text-sm font-semibold text-brand-tint">{{ $label }}</span>

    <span class="relative flex flex-wrap items-baseline gap-3">
        <span class="text-[40px] font-extrabold leading-none lg:text-[60px]">{{ $value }}</span>
        <span class="text-xl font-bold text-gold-light lg:text-[22px]">FCFA</span>
    </span>

    @if ($generatedAt)
        <span class="relative text-[13px] text-brand-tint">{{ $generatedAt }}</span>
    @endif

    @if (count($chips) > 0)
        <span class="relative mt-auto flex flex-wrap gap-2.5">
            @foreach ($chips as $chip)
                <span class="rounded-full bg-brand-dark px-3.5 py-2 text-[13px] font-semibold">{{ $chip }}</span>
            @endforeach
        </span>
    @endif
</div>
