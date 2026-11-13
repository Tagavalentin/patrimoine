@props(['label' => null, 'error' => null])

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $attributes->get('id') ?? $attributes->get('name') }}" class="mb-1.5 block text-[13px] font-semibold text-ink">{{ $label }}</label>
    @endif
    <input {{ $attributes->except(['class', 'label', 'error'])->merge(['class' => 'h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30']) }}>
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-error">{{ $error }}</p>
    @endif
</div>
