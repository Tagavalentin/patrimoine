@props(['class' => ''])

<div {{ $attributes->merge(['class' => "rounded-3xl border border-line bg-white p-7 {$class}"]) }}>
    {{ $slot }}
</div>
