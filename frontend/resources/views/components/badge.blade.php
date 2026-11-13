@props(['variant' => 'neutral'])

@php
$classes = [
    'neutral' => 'bg-disposed-bg text-disposed-text',
    'acquired' => 'bg-acquired-bg text-acquired-text',
    'assigned' => 'bg-assigned-bg text-assigned-text',
    'in_maintenance' => 'bg-warn-bg text-warn-text',
    'decommissioned' => 'bg-decommissioned-bg text-decommissioned-text',
    'disposed' => 'bg-disposed-bg text-disposed-text',
    'urgent' => 'bg-decommissioned-bg text-decommissioned-text',
    'normal' => 'bg-acquired-bg text-acquired-text',
    'low' => 'bg-disposed-bg text-disposed-text',
    'warn' => 'bg-warn-bg text-warn-text',
][$variant] ?? 'bg-disposed-bg text-disposed-text';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1.5 text-[13px] font-bold {$classes}"]) }}>
    {{ $slot }}
</span>
