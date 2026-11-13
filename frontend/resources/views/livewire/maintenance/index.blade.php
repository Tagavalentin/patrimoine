<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Maintenance</h2>
            <p class="mt-1 text-sm text-muted">{{ $total }} demande(s)</p>
        </div>
        <x-button variant="gold" href="{{ route('maintenance.create') }}">+ Nouvelle demande</x-button>
    </div>

    {{-- Filtres --}}
    <x-card class="mt-6">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="mb-1.5 block text-[13px] font-semibold text-ink">Statut</label>
                <select id="status" wire:model.live="status"
                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">Tous</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="mb-1.5 block text-[13px] font-semibold text-ink">Priorité</label>
                <select id="priority" wire:model.live="priority"
                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">Toutes</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
            <x-button variant="neutral" wire:click="$refresh" class="mt-3">Réessayer</x-button>
        </x-card>
    @endif

    {{-- Tableau --}}
    <x-card class="mt-6 overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                    <tr>
                        <th class="px-5 py-3.5">Référence</th>
                        <th class="px-5 py-3.5">Bien</th>
                        <th class="px-5 py-3.5">Priorité</th>
                        <th class="px-5 py-3.5">Statut</th>
                        <th class="px-5 py-3.5">Demandé par</th>
                        <th class="px-5 py-3.5">Créée le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($requests as $req)
                        @php
                            $stLabel = $statusPresenter::maintenance($req['status'] ?? null)['label'];
                            $prLabel = $priorityPresenter::get($req['priority'] ?? null)['label'];
                        @endphp
                        <tr class="h-[52px] transition-colors hover:bg-page">
                            <td class="px-5 py-3 font-semibold text-brand">{{ $req['reference'] }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('maintenance.show', $req['id']) }}" class="font-medium text-ink hover:text-brand hover:underline">
                                    {{ $req['asset']['designation'] ?? '—' }}
                                </a>
                                <div class="text-xs text-muted">{{ $req['asset']['code'] ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3"><x-badge :variant="$req['priority'] ?? 'neutral'">{{ $prLabel }}</x-badge></td>
                            <td class="px-5 py-3"><x-badge :variant="$req['status'] ?? 'neutral'">{{ $stLabel }}</x-badge></td>
                            <td class="px-5 py-3 text-muted">{{ $req['requested_by']['name'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted">{{ ! empty($req['created_at']) ? \Illuminate\Support\Carbon::parse($req['created_at'])->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucune demande trouvée.</td></tr>
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
