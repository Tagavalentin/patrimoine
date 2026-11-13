<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Inventaires</h2>
            <p class="mt-1 text-sm text-muted">{{ $total }} campagne(s)</p>
        </div>
        <x-button variant="gold" href="{{ route('inventory.create') }}">+ Nouvelle campagne</x-button>
    </div>

    {{-- Filtres --}}
    <x-card class="mt-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <x-input wire:model.live.debounce.400ms="search" label="Recherche" placeholder="Référence ou nom…" />
            </div>
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
        </div>
    </x-card>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
            <x-button variant="neutral" wire:click="$refresh" class="mt-3">Réessayer</x-button>
        </x-card>
    @endif

    <x-card class="mt-6 overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                    <tr>
                        <th class="px-5 py-3.5">Référence</th>
                        <th class="px-5 py-3.5">Nom</th>
                        <th class="px-5 py-3.5">Périmètre</th>
                        <th class="px-5 py-3.5">Statut</th>
                        <th class="px-5 py-3.5">Créée par</th>
                        <th class="px-5 py-3.5">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($campaigns as $c)
                        @php $stLabel = $presenter::inventory($c['status'] ?? null)['label']; @endphp
                        <tr class="h-[52px] transition-colors hover:bg-page">
                            <td class="px-5 py-3 font-semibold text-brand">{{ $c['reference'] }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('inventory.show', $c['id']) }}" class="font-medium text-ink hover:text-brand hover:underline">
                                    {{ $c['name'] }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-muted">
                                {{ $c['scope']['department']['name'] ?? $c['scope']['location']['name'] ?? 'Patrimoine complet' }}
                            </td>
                            <td class="px-5 py-3"><x-badge :variant="$c['status'] ?? 'neutral'">{{ $stLabel }}</x-badge></td>
                            <td class="px-5 py-3 text-muted">{{ $c['created_by']['name'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted">{{ ! empty($c['created_at']) ? \Illuminate\Support\Carbon::parse($c['created_at'])->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucune campagne trouvée.</td></tr>
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
