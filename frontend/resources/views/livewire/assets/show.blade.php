<div>
    <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>

    @if ($error || ! $assetData)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error ?? 'Bien introuvable.' }}</p>
        </x-card>
        @return
    @endif

    @php
        $status = $assetData['status'] ?? null;
        $statusLabel = $presenter::asset($status)['label'];
        $tabs = [
            'informations' => 'Informations',
            'affectations' => 'Affectations',
            'maintenance' => 'Maintenance',
            'amortissement' => 'Amortissement',
        ];
    @endphp

    {{-- En-tête du bien --}}
    <x-card class="mt-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs font-semibold text-muted">{{ $assetData['code'] }}</p>
                <h2 class="mt-1 text-2xl font-extrabold text-ink">{{ $assetData['designation'] }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
                    <span>{{ $assetData['category']['name'] ?? '—' }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $assetData['location']['name'] ?? '—' }}</span>
                    @if (! empty($assetData['department']))
                        <span aria-hidden="true">·</span>
                        <span>{{ $assetData['department']['name'] }}</span>
                    @endif
                </div>
            </div>
            <x-badge :variant="$status ?? 'neutral'" class="px-3.5 py-2 text-sm">{{ $statusLabel }}</x-badge>
        </div>
    </x-card>

    {{-- Onglets --}}
    <div class="mt-6 flex gap-1 overflow-x-auto border-b border-line" role="tablist" aria-label="Sections du bien">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    wire:click="selectTab('{{ $key }}')"
                    class="min-h-11 flex-none rounded-t-xl px-4 text-sm font-bold transition-colors {{ $tab === $key ? 'bg-white text-brand shadow-sm ring-1 ring-line' : 'text-muted hover:text-ink' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Contenu des onglets --}}
    <div class="mt-6">
        <div class="{{ $tab === 'informations' ? '' : 'hidden' }}">
            <x-card>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Informations</h3>
                <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-muted">Date d'acquisition</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $assetData['acquisition_date'] ? \Illuminate\Support\Carbon::parse($assetData['acquisition_date'])->format('d/m/Y') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Valeur d'acquisition</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ isset($assetData['acquisition_value']) ? number_format((float) $assetData['acquisition_value'], 0, ',', ' ').' FCFA' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Durée de vie</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $assetData['useful_life_years'] ?? '—' }} an(s)</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Catégorie</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $assetData['category']['name'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Localisation</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $assetData['location']['name'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Service responsable</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $assetData['department']['name'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Enregistré le</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ ! empty($assetData['created_at']) ? \Illuminate\Support\Carbon::parse($assetData['created_at'])->format('d/m/Y') : '—' }}</dd>
                    </div>
                </dl>
                @if (! empty($assetData['description']))
                    <div class="mt-5 border-t border-line pt-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-muted">Description</h4>
                        <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $assetData['description'] }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="{{ $tab === 'affectations' ? '' : 'hidden' }}">
            <x-card class="overflow-hidden p-0">
                <div class="border-b border-line px-7 py-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Historique des affectations</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                            <tr>
                                <th class="px-5 py-3">Début</th>
                                <th class="px-5 py-3">Fin</th>
                                <th class="px-5 py-3">Service</th>
                                <th class="px-5 py-3">Responsable</th>
                                <th class="px-5 py-3">Motif</th>
                                <th class="px-5 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($assignments as $a)
                                <tr class="h-[52px] hover:bg-page">
                                    <td class="px-5 py-3">{{ $a['start_date'] ? \Illuminate\Support\Carbon::parse($a['start_date'])->format('d/m/Y') : '—' }}</td>
                                    <td class="px-5 py-3">{{ $a['end_date'] ? \Illuminate\Support\Carbon::parse($a['end_date'])->format('d/m/Y') : 'En cours' }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $a['department']['name'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $a['responsible']['name'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $a['reason'] ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        @if ($a['is_active'] ?? false)
                                            <x-badge variant="assigned">Active</x-badge>
                                        @else
                                            <x-badge variant="neutral">Clôturée</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucune affectation enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="{{ $tab === 'maintenance' ? '' : 'hidden' }}">
            <x-card class="overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-7 py-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Demandes de maintenance</h3>
                    <x-button variant="neutral" href="{{ route('maintenance.create') }}" class="px-4">Nouvelle demande</x-button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                            <tr>
                                <th class="px-5 py-3">Référence</th>
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3">Priorité</th>
                                <th class="px-5 py-3">Statut</th>
                                <th class="px-5 py-3">Créée le</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($maintenanceRequests as $req)
                                @php
                                    $ms = $presenter::maintenance($req['status'] ?? null)['label'];
                                    $mp = $priorityPresenter::get($req['priority'] ?? null)['label'];
                                @endphp
                                <tr class="h-[52px] hover:bg-page">
                                    <td class="px-5 py-3 font-semibold text-brand">{{ $req['reference'] ?? '#'.$req['id'] }}</td>
                                    <td class="max-w-xs truncate px-5 py-3 text-muted">{{ $req['description'] ?? '—' }}</td>
                                    <td class="px-5 py-3"><x-badge :variant="$req['priority'] ?? 'neutral'">{{ $mp }}</x-badge></td>
                                    <td class="px-5 py-3"><x-badge :variant="$req['status'] ?? 'neutral'">{{ $ms }}</x-badge></td>
                                    <td class="px-5 py-3 text-muted">{{ ! empty($req['created_at']) ? \Illuminate\Support\Carbon::parse($req['created_at'])->format('d/m/Y') : '—' }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('maintenance.show', $req['id']) }}" class="font-semibold text-brand hover:underline">Voir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucune demande de maintenance pour ce bien.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="{{ $tab === 'amortissement' ? '' : 'hidden' }}">
            <x-card>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Valorisation</h3>
                @if ($depreciation)
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl bg-page p-4">
                            <dt class="text-muted">Valeur nette comptable</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ number_format((float) $depreciation['net_book_value'], 0, ',', ' ') }} F</dd>
                        </div>
                        <div class="rounded-2xl bg-page p-4">
                            <dt class="text-muted">Amortissement cumulé</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ number_format((float) $depreciation['accumulated_depreciation'], 0, ',', ' ') }} F</dd>
                        </div>
                        <div class="rounded-2xl bg-page p-4">
                            <dt class="text-muted">Dotation mensuelle</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ number_format((float) $depreciation['monthly_depreciation'], 0, ',', ' ') }} F</dd>
                        </div>
                        <div class="rounded-2xl bg-page p-4">
                            <dt class="text-muted">Mois écoulés / restants</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ $depreciation['elapsed_months'] }} / {{ $depreciation['remaining_months'] }}</dd>
                        </div>
                    </dl>
                    @if ($depreciation['fully_depreciated'] ?? false)
                        <p class="mt-4 rounded-xl bg-warn-bg px-4 py-3 text-sm font-semibold text-warn-text">Bien totalement amorti.</p>
                    @endif
                @else
                    <p class="mt-4 text-sm text-muted">Valorisation indisponible.</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
