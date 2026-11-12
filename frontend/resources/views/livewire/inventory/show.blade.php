<div>
    <a href="{{ route('inventory.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>

    @if ($error && ! $campaign)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
        @return
    @endif

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif
    @if ($notice)
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
    @endif

    @if ($campaign)
        @php $st = $presenter::inventory($status); @endphp

        {{-- En-tête + actions --}}
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div>
                <p class="font-mono text-xs text-slate-500">{{ $campaign['reference'] }}</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-800">{{ $campaign['name'] }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $campaign['scope']['department']['name'] ?? $campaign['scope']['location']['name'] ?? 'Patrimoine complet' }}
                    — créée par {{ $campaign['created_by']['name'] ?? '—' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $st['class'] }}">{{ $st['label'] }}</span>
                @if ($canManage && $status === 'draft')
                    <button wire:click="start" wire:confirm="Démarrer la campagne ? Les lignes deviendront contrôlables."
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        Démarrer
                    </button>
                @endif
                @if ($canManage && $status === 'in_progress')
                    <button wire:click="complete" wire:confirm="Clôturer la campagne ? Les lignes non contrôlées seront marquées manquantes."
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Clôturer
                    </button>
                @endif
            </div>
        </div>

        {{-- Synthèse --}}
        @if ($summary)
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-6">
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Total</p>
                    <p class="text-xl font-semibold">{{ $summary['lines_total'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Trouvés</p>
                    <p class="text-xl font-semibold text-emerald-700">{{ $summary['found'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Manquants</p>
                    <p class="text-xl font-semibold text-red-700">{{ $summary['missing'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Endommagés</p>
                    <p class="text-xl font-semibold text-amber-700">{{ $summary['damaged'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">En attente</p>
                    <p class="text-xl font-semibold text-slate-600">{{ $summary['pending'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Taux d'écarts</p>
                    <p class="text-xl font-semibold">{{ $summary['discrepancy_rate'] ?? 0 }} %</p>
                </div>
            </div>
        @endif

        {{-- Filtre lignes --}}
        <div class="mt-6 flex items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div>
                <label for="checkStatusFilter" class="block text-xs font-medium text-slate-500">Résultat du contrôle</label>
                <select id="checkStatusFilter" wire:model.live="checkStatusFilter"
                        class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tous</option>
                    <option value="pending">En attente</option>
                    <option value="found">Trouvé</option>
                    <option value="missing">Manquant</option>
                    <option value="damaged">Endommagé</option>
                </select>
            </div>
            <span class="pb-2 text-sm text-slate-500">{{ $total }} ligne(s)</span>
        </div>

        {{-- Lignes --}}
        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Bien</th>
                        <th class="px-4 py-3">Emplacement</th>
                        <th class="px-4 py-3">Contrôle</th>
                        <th class="px-4 py-3">Notes</th>
                        <th class="px-4 py-3">Contrôlé par</th>
                        @if ($canCheck && $status === 'in_progress')
                            <th class="px-4 py-3 text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lines as $line)
                        @php $cs = \App\Support\CheckStatusPresenter::get($line['check_status'] ?? null); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs">{{ $line['asset']['code'] ?? '' }}</span>
                                <div class="font-medium">{{ $line['asset']['designation'] ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $line['asset']['department_name'] ?? $line['asset']['location_name'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $cs['class'] }}">{{ $cs['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $line['notes'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $line['checked_by_name'] ?? '—' }}</td>
                            @if ($canCheck && $status === 'in_progress')
                                <td class="px-4 py-3 text-right">
                                    @if ($checkingLineId === $line['id'])
                                        <div class="flex items-center justify-end gap-2">
                                            <select wire:model="lineCheckStatus" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                                <option value="found">Trouvé</option>
                                                <option value="missing">Manquant</option>
                                                <option value="damaged">Endommagé</option>
                                            </select>
                                            <input type="text" wire:model="lineNotes" placeholder="Note…"
                                                   class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                            <button wire:click="saveCheck" class="rounded-lg bg-indigo-600 px-2 py-1 text-xs text-white">OK</button>
                                            <button wire:click="cancelCheck" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">✕</button>
                                        </div>
                                    @else
                                        <button wire:click="beginCheck({{ $line['id'] }})"
                                                class="rounded-lg border border-slate-300 px-3 py-1 text-xs hover:bg-slate-50">
                                            Contrôler
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune ligne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lastPage > 1)
            <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                <span>Page {{ $page }} / {{ $lastPage }}</span>
                <div class="flex gap-2">
                    <button wire:click="gotoPage({{ $page - 1 }})" @disabled($page <= 1)
                            class="rounded-lg border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40">Précédent</button>
                    <button wire:click="gotoPage({{ $page + 1 }})" @disabled($page >= $lastPage)
                            class="rounded-lg border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40">Suivant</button>
                </div>
            </div>
        @endif
    @endif
</div>
