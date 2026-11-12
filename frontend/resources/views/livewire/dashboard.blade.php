<div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Tableau de bord</h1>
            <p class="mt-1 text-sm text-slate-500">
                Données consolidées en temps réel — généré le
                {{ isset($data['generated_at']) ? \Illuminate\Support\Carbon::parse($data['generated_at'])->format('d/m/Y à H:i') : '—' }}
            </p>
        </div>
    </div>

    @if ($error)
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $error }}
        </div>
    @endif

    @if ($data)
        {{-- Indicateurs clés --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Biens suivis</p>
                <p class="mt-1 text-3xl font-semibold text-slate-800">{{ $data['assets']['total'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Valeur nette comptable</p>
                <p class="mt-1 text-3xl font-semibold text-slate-800">
                    {{ isset($data['assets']['net_book_value']) ? number_format((float) $data['assets']['net_book_value'], 0, ',', ' ') : '—' }}
                    <span class="text-base font-normal text-slate-500">FCFA</span>
                </p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Demandes de maintenance ouvertes</p>
                <p class="mt-1 text-3xl font-semibold text-slate-800">{{ $data['maintenance']['open'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Dernière campagne d'inventaire</p>
                <p class="mt-1 text-lg font-semibold text-slate-800">
                    {{ $data['inventory']['latest_campaign'] ?? '—' }}
                </p>
                @if (! empty($data['inventory']['latest_status']))
                    @php $inv = $presenter::inventory($data['inventory']['latest_status']); @endphp
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $inv['class'] }}">{{ $inv['label'] }}</span>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Biens par statut --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Biens par statut</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach (($data['assets']['by_status'] ?? []) as $status => $count)
                        @php $s = $presenter::asset($status); @endphp
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <span class="inline-flex items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s['class'] }}">{{ $s['label'] }}</span>
                            </span>
                            <span class="font-medium text-slate-700">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
                @if (isset($data['assets']['accumulated_depreciation']))
                    <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        Amortissement cumulé :
                        {{ number_format((float) $data['assets']['accumulated_depreciation'], 0, ',', ' ') }} FCFA
                    </p>
                @endif
            </div>

            {{-- Maintenance par statut --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Demandes de maintenance</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach (($data['maintenance']['by_status'] ?? []) as $status => $count)
                        @php $s = $presenter::maintenance($status); @endphp
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <span class="inline-flex items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s['class'] }}">{{ $s['label'] }}</span>
                            </span>
                            <span class="font-medium text-slate-700">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Dernier inventaire --}}
        @if (! empty($data['inventory']['latest_summary']))
            @php $sum = $data['inventory']['latest_summary']; @endphp
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">
                    Synthèse — {{ $data['inventory']['latest_campaign'] }}
                </h2>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
                    <div><p class="text-slate-500">Total lignes</p><p class="text-lg font-semibold">{{ $sum['lines_total'] ?? '—' }}</p></div>
                    <div><p class="text-slate-500">Trouvés</p><p class="text-lg font-semibold text-emerald-700">{{ $sum['found'] ?? '—' }}</p></div>
                    <div><p class="text-slate-500">Manquants</p><p class="text-lg font-semibold text-red-700">{{ $sum['missing'] ?? '—' }}</p></div>
                    <div><p class="text-slate-500">Endommagés</p><p class="text-lg font-semibold text-amber-700">{{ $sum['damaged'] ?? '—' }}</p></div>
                    <div><p class="text-slate-500">Taux d'écarts</p><p class="text-lg font-semibold">{{ isset($sum['discrepancy_rate']) ? $sum['discrepancy_rate'].' %' : '—' }}</p></div>
                </div>
            </div>
        @endif
    @endif

    {{-- Utilisateur connecté --}}
    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Utilisateur connecté</h2>
        @if ($me)
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Nom</dt><dd class="font-medium">{{ $me['name'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">E-mail</dt><dd class="font-medium">{{ $me['email'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Rôle</dt><dd class="font-medium">{{ $me['role']['name'] ?? '—' }}</dd></div>
            </dl>
        @else
            <p class="mt-4 text-sm text-slate-500">Profil indisponible.</p>
        @endif
    </div>
</div>
