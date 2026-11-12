<div>
    <h1 class="text-2xl font-semibold text-slate-800">Rapports et exports</h1>
    <p class="mt-1 text-sm text-slate-500">Exports CSV générés par le service de reporting — chaque export est journalisé.</p>

    {{-- Exports --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            'assets' => ['Biens', 'Liste complète des biens du patrimoine avec statuts et valeurs.'],
            'maintenance' => ['Demandes de maintenance', 'Demandes, statuts, priorités et coûts des interventions.'],
            'inventory' => ['Campagnes d\'inventaire', 'Campagnes, lignes contrôlées et écarts constatés.'],
        ] as $type => $info)
            <div class="flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-medium text-slate-800">{{ $info[0] }}</h2>
                <p class="mt-1 flex-1 text-sm text-slate-500">{{ $info[1] }}</p>
                <a href="{{ route('reports.export', $type) }}"
                   class="mt-4 inline-flex items-center justify-center rounded-lg border border-indigo-600 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                    Télécharger le CSV
                </a>
            </div>
        @endforeach
    </div>

    {{-- Journal d'audit --}}
    @if ($canViewAudit)
        <div class="mt-8">
            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Journal des exports</h2>

            @if ($error)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
            @endif

            <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Format</th>
                            <th class="px-4 py-3 text-right">Lignes</th>
                            <th class="px-4 py-3">Auteur</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="px-4 py-3 text-slate-600">{{ ! empty($log['created_at']) ? \Illuminate\Support\Carbon::parse($log['created_at'])->format('d/m/Y H:i') : '—' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $log['export_type'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ strtoupper($log['format'] ?? '—') }}</td>
                                <td class="px-4 py-3 text-right">{{ $log['rows_count'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $log['actor_name'] ?? $log['actor_email'] ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log['ip_address'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun export journalisé.</td></tr>
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
        </div>
    @endif
</div>
