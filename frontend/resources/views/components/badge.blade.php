@props(['variant' => 'neutral'])

@php
$classes = [
    // Neutres / parcours
    'neutral' => 'bg-disposed-bg text-disposed-text',
    'draft' => 'bg-disposed-bg text-disposed-text',
    'cancelled' => 'bg-disposed-bg text-disposed-text',
    'pending' => 'bg-acquired-bg text-acquired-text',

    // Biens
    'acquired' => 'bg-acquired-bg text-acquired-text',
    'assigned' => 'bg-assigned-bg text-assigned-text',
    'in_maintenance' => 'bg-warn-bg text-warn-text',
    'decommissioned' => 'bg-decommissioned-bg text-decommissioned-text',
    'disposed' => 'bg-disposed-bg text-disposed-text',

    // Maintenance
    'requested' => 'bg-acquired-bg text-acquired-text',
    'scheduled' => 'bg-brand-tint text-brand-dark',
    'completed' => 'bg-brand-soft text-brand',

    // Inventaire / contrôle
    'found' => 'bg-assigned-bg text-assigned-text',
    'missing' => 'bg-decommissioned-bg text-decommissioned-text',
    'damaged' => 'bg-warn-bg text-warn-text',

    // Priorités
    'urgent' => 'bg-decommissioned-bg text-decommissioned-text',
    'high' => 'bg-warn-bg text-warn-text',
    'normal' => 'bg-acquired-bg text-acquired-text',
    'low' => 'bg-disposed-bg text-disposed-text',

    'warn' => 'bg-warn-bg text-warn-text',
][$variant] ?? 'bg-disposed-bg text-disposed-text';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1.5 text-[13px] font-bold {$classes}"]) }}>
    {{ $slot }}
</span>
