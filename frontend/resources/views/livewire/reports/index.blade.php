<div>
    <h2 class="text-2xl font-extrabold text-ink">Rapports et exports</h2>
    <p class="mt-1 text-sm text-muted">Exports CSV générés par le service de reporting — chaque export est journalisé.</p>

    {{-- Exports --}}
    <div class="mt-6 grid gap-5 sm:grid-cols-3">
        @foreach ([
            'assets' => ['Biens', 'Liste complète des biens du patrimoine avec statuts et valeurs.'],
            'maintenance' => ['Demandes de maintenance', 'Demandes, statuts, priorités et coûts des interventions.'],
            'inventory' => ['Campagnes d\'inventaire', 'Campagnes, lignes contrôlées et écarts constatés.'],
        ] as $type => $info)
            <x-card class="flex flex-col gap-2">
                <h3 class="font-bold text-ink">{{ $info[0] }}</h3>
                <p class="flex-1 text-sm text-muted">{{ $info[1] }}</p>
                <x-button variant="outline" href="{{ route('reports.export', $type) }}" class="mt-2 w-full">Télécharger le CSV</x-button>
            </x-card>
        @endforeach
    </div>

    {{-- Journal d'audit --}}
    @if ($canViewAudit)
        <div class="mt-8">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Journal des exports</h3>

            @if ($error)
                <x-card class="mt-4 border-decommissioned-bg">
                    <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
                    <x-button variant="neutral" wire:click="$refresh" class="mt-3">Réessayer</x-button>
                </x-card>
            @endif

            <x-card class="mt-4 overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                            <tr>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-5 py-3.5">Type</th>
                                <th class="px-5 py-3.5">Format</th>
                                <th class="px-5 py-3.5 text-right">Lignes</th>
                                <th class="px-5 py-3.5">Auteur</th>
                                <th class="px-5 py-3.5">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($auditLogs as $log)
                                <tr class="h-[52px] hover:bg-page">
                                    <td class="px-5 py-3 text-muted">{{ ! empty($log['created_at']) ? \Illuminate\Support\Carbon::parse($log['created_at'])->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="px-5 py-3 font-semibold text-brand">{{ $log['export_type'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-muted">{{ strtoupper($log['format'] ?? '—') }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">{{ $log['rows_count'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $log['actor_name'] ?? $log['actor_email'] ?? '—' }}</td>
                                    <td class="px-5 py-3 font-mono text-xs text-muted">{{ $log['ip_address'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucun export journalisé.</td></tr>
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
        </div>
    @endif
</div>
