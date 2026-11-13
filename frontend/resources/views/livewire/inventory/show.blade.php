<div>
    <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>

    @if ($error && ! $campaign)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
        @return
    @endif

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
    @endif
    @if ($notice)
        <x-card class="mt-6 border-brand bg-brand-soft/50">
            <p class="text-sm font-semibold text-brand-dark">{{ $notice }}</p>
        </x-card>
    @endif

    @if ($campaign)
        @php $stLabel = $presenter::inventory($status)['label']; @endphp

        {{-- En-tête + actions --}}
        <x-card class="mt-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-mono text-xs font-semibold text-muted">{{ $campaign['reference'] }}</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-ink">{{ $campaign['name'] }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ $campaign['scope']['department']['name'] ?? $campaign['scope']['location']['name'] ?? 'Patrimoine complet' }}
                        — créée par {{ $campaign['created_by']['name'] ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <x-badge :variant="$status ?? 'neutral'" class="px-3.5 py-2 text-sm">{{ $stLabel }}</x-badge>
                    @if ($canManage && $status === 'draft')
                        <x-button variant="gold" wire:click="start" wire:confirm="Démarrer la campagne ? Les lignes deviendront contrôlables.">Démarrer</x-button>
                    @endif
                    @if ($canManage && $status === 'in_progress')
                        <x-button variant="primary" wire:click="complete" wire:confirm="Clôturer la campagne ? Les lignes non contrôlées seront marquées manquantes.">Clôturer</x-button>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Synthèse --}}
        @if ($summary)
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    'Total' => [$summary['lines_total'] ?? 0, 'text-ink'],
                    'Trouvés' => [$summary['found'] ?? 0, 'text-brand'],
                    'Manquants' => [$summary['missing'] ?? 0, 'text-error'],
                    'Endommagés' => [$summary['damaged'] ?? 0, 'text-warn-text'],
                    'En attente' => [$summary['pending'] ?? 0, 'text-muted'],
                    "Taux d'écarts" => [($summary['discrepancy_rate'] ?? 0).' %', 'text-ink'],
                ] as $label => [$value, $color])
                    <x-card class="p-4 text-center">
                        <p class="text-xs font-semibold text-muted">{{ $label }}</p>
                        <p class="mt-1 text-xl font-extrabold {{ $color }}">{{ $value }}</p>
                    </x-card>
                @endforeach
            </div>
        @endif

        {{-- Filtre lignes --}}
        <x-card class="mt-6">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="checkStatusFilter" class="mb-1.5 block text-[13px] font-semibold text-ink">Résultat du contrôle</label>
                    <select id="checkStatusFilter" wire:model.live="checkStatusFilter"
                            class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                        <option value="">Tous</option>
                        <option value="pending">En attente</option>
                        <option value="found">Trouvé</option>
                        <option value="missing">Manquant</option>
                        <option value="damaged">Endommagé</option>
                    </select>
                </div>
                <span class="pb-3 text-sm text-muted">{{ $total }} ligne(s)</span>
            </div>
        </x-card>

        {{-- Lignes --}}
        <x-card class="mt-6 overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                        <tr>
                            <th class="px-5 py-3.5">Bien</th>
                            <th class="px-5 py-3.5">Emplacement</th>
                            <th class="px-5 py-3.5">Contrôle</th>
                            <th class="px-5 py-3.5">Notes</th>
                            <th class="px-5 py-3.5">Contrôlé par</th>
                            @if ($canCheck && $status === 'in_progress')
                                <th class="px-5 py-3.5 text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($lines as $line)
                            @php $csLabel = \App\Support\CheckStatusPresenter::get($line['check_status'] ?? null)['label']; @endphp
                            <tr class="hover:bg-page">
                                <td class="px-5 py-3.5">
                                    <span class="font-mono text-xs font-semibold text-brand">{{ $line['asset']['code'] ?? '' }}</span>
                                    <div class="font-medium text-ink">{{ $line['asset']['designation'] ?? '—' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-muted">
                                    {{ $line['asset']['department_name'] ?? $line['asset']['location_name'] ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5"><x-badge :variant="$line['check_status'] ?? 'pending'">{{ $csLabel }}</x-badge></td>
                                <td class="px-5 py-3.5 text-muted">{{ $line['notes'] ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-muted">{{ $line['checked_by_name'] ?? '—' }}</td>
                                @if ($canCheck && $status === 'in_progress')
                                    <td class="px-5 py-3.5 text-right">
                                        @if ($checkingLineId === $line['id'])
                                            <div class="flex items-center justify-end gap-2">
                                                <select wire:model="lineCheckStatus" aria-label="Résultat du contrôle"
                                                        class="h-11 rounded-xl border border-line-strong bg-white px-3 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                                                    <option value="found">Trouvé</option>
                                                    <option value="missing">Manquant</option>
                                                    <option value="damaged">Endommagé</option>
                                                </select>
                                                <input type="text" wire:model="lineNotes" placeholder="Note…" aria-label="Note de contrôle"
                                                       class="h-11 w-36 rounded-xl border border-line-strong bg-white px-3 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none">
                                                <button type="button" wire:click="saveCheck"
                                                        class="inline-flex min-h-11 items-center rounded-xl bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark">OK</button>
                                                <button type="button" wire:click="cancelCheck" aria-label="Annuler le contrôle"
                                                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line-strong text-ink hover:bg-page">
                                                    <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
                                                </button>
                                            </div>
                                        @else
                                            <button type="button" wire:click="beginCheck({{ $line['id'] }})"
                                                    class="inline-flex min-h-11 items-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink hover:bg-page">
                                                Contrôler
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucune ligne.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lastPage > 1)
                <div class="flex items-center justify-between border-t border-line px-5 py-4 text-sm text-muted">
                    <span>Page {{ $page }} / {{ $lastPage }}</span>
                    <div class="flex gap-2">
                        <button type="button" wire:click="gotoPage({{ $page - 1 }})" @disabled($page <= 1)
                                class="inline-flex min-h-11 items-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink transition-colors hover:bg-page disabled:opacity-40">Précédent</button>
                        <button type="button" wire:click="gotoPage({{ $page + 1 }})" @disabled($page >= $lastPage)
                                class="inline-flex min-h-11 items-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink transition-colors hover:bg-page disabled:opacity-40">Suivant</button>
                    </div>
                </div>
            @endif
        </x-card>
    @endif
</div>
