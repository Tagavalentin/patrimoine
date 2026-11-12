<div>
    <a href="{{ route('assets.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>

    @if ($error || ! $assetData)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $error ?? 'Bien introuvable.' }}
        </div>
        @return
    @endif

    @php $s = $presenter::asset($assetData['status'] ?? null); @endphp

    {{-- En-tête --}}
    <div class="mt-4 flex flex-wrap items-start justify-between gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div>
            <p class="font-mono text-xs text-slate-500">{{ $assetData['code'] }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-800">{{ $assetData['designation'] }}</h1>
            <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-600">
                <span>{{ $assetData['category']['name'] ?? '—' }}</span>
                <span>·</span>
                <span>{{ $assetData['location']['name'] ?? '—' }}</span>
                @if (! empty($assetData['department']))
                    <span>·</span>
                    <span>{{ $assetData['department']['name'] }}</span>
                @endif
            </div>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $s['class'] }}">{{ $s['label'] }}</span>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Informations --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Informations</h2>
            <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Date d'acquisition</dt>
                    <dd class="font-medium">{{ $assetData['acquisition_date'] ? \Illuminate\Support\Carbon::parse($assetData['acquisition_date'])->format('d/m/Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Valeur d'acquisition</dt>
                    <dd class="font-medium">{{ isset($assetData['acquisition_value']) ? number_format((float) $assetData['acquisition_value'], 0, ',', ' ') : '—' }} FCFA</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Durée de vie</dt>
                    <dd class="font-medium">{{ $assetData['useful_life_years'] ?? '—' }} an(s)</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Enregistré le</dt>
                    <dd class="font-medium">{{ ! empty($assetData['created_at']) ? \Illuminate\Support\Carbon::parse($assetData['created_at'])->format('d/m/Y') : '—' }}</dd>
                </div>
            </dl>
            @if (! empty($assetData['description']))
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $assetData['description'] }}</p>
                </div>
            @endif
        </div>

        {{-- Valorisation --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Valorisation</h2>
            @if ($depreciation)
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Valeur nette comptable</dt><dd class="font-semibold">{{ number_format((float) $depreciation['net_book_value'], 0, ',', ' ') }} F</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Amortissement cumulé</dt><dd>{{ number_format((float) $depreciation['accumulated_depreciation'], 0, ',', ' ') }} F</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Dotation mensuelle</dt><dd>{{ number_format((float) $depreciation['monthly_depreciation'], 0, ',', ' ') }} F</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Mois écoulés / restants</dt><dd>{{ $depreciation['elapsed_months'] }} / {{ $depreciation['remaining_months'] }}</dd></div>
                </dl>
                @if ($depreciation['fully_depreciated'] ?? false)
                    <p class="mt-3 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600">Bien totalement amorti.</p>
                @endif
            @else
                <p class="mt-4 text-sm text-slate-500">Valorisation indisponible.</p>
            @endif
        </div>
    </div>

    {{-- Historique des affectations --}}
    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Historique des affectations</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">Début</th>
                        <th class="py-2 pr-4">Fin</th>
                        <th class="py-2 pr-4">Service</th>
                        <th class="py-2 pr-4">Responsable</th>
                        <th class="py-2 pr-4">Motif</th>
                        <th class="py-2">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $a)
                        <tr>
                            <td class="py-2 pr-4">{{ $a['start_date'] ? \Illuminate\Support\Carbon::parse($a['start_date'])->format('d/m/Y') : '—' }}</td>
                            <td class="py-2 pr-4">{{ $a['end_date'] ? \Illuminate\Support\Carbon::parse($a['end_date'])->format('d/m/Y') : 'En cours' }}</td>
                            <td class="py-2 pr-4">{{ $a['department']['name'] ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $a['responsible']['name'] ?? '—' }}</td>
                            <td class="py-2 pr-4 text-slate-600">{{ $a['reason'] ?? '—' }}</td>
                            <td class="py-2">
                                @if ($a['is_active'] ?? false)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Clôturée</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">Aucune affectation enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
