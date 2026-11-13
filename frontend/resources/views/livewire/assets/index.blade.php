<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Biens</h2>
            <p class="mt-1 text-sm text-muted">{{ $total }} bien(s) dans le patrimoine</p>
        </div>
    </div>

    {{-- Filtres --}}
    <x-card class="mt-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <x-input wire:model.live.debounce.400ms="search" label="Recherche" placeholder="Code ou désignation…" />
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
            <div>
                <label for="category" class="mb-1.5 block text-[13px] font-semibold text-ink">Catégorie</label>
                <select id="category" wire:model.live="categoryId"
                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">Toutes</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
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
                        <th class="px-5 py-3.5">Code</th>
                        <th class="px-5 py-3.5">Désignation</th>
                        <th class="px-5 py-3.5">Catégorie</th>
                        <th class="px-5 py-3.5">Localisation</th>
                        <th class="px-5 py-3.5">Statut</th>
                        <th class="px-5 py-3.5 text-right">Valeur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($assets as $asset)
                        @php
                            $status = $asset['status'] ?? null;
                            $label = $presenter::asset($status)['label'];
                        @endphp
                        <tr class="h-[52px] transition-colors hover:bg-page">
                            <td class="px-5 py-3 font-semibold text-brand">{{ $asset['code'] }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('assets.show', $asset['id']) }}" class="font-medium text-ink hover:text-brand hover:underline">
                                    {{ $asset['designation'] }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-muted">{{ $asset['category']['name'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted">{{ $asset['location']['name'] ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <x-badge :variant="$status ?? 'neutral'">{{ $label }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-ink">
                                {{ isset($asset['acquisition_value']) ? number_format((float) $asset['acquisition_value'], 0, ',', ' ') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Aucun bien trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($lastPage > 1)
            <div class="flex items-center justify-between border-t border-line px-5 py-4 text-sm text-muted">
                <span>Page {{ $page }} / {{ $lastPage }}</span>
                <div class="flex gap-2">
                    <button type="button" wire:click="gotoPage({{ $page - 1 }})" @disabled($page <= 1)
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink transition-colors hover:bg-page disabled:opacity-40">Précédent</button>
                    <button type="button" wire:click="gotoPage({{ $page + 1 }})" @disabled($page >= $lastPage)
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink transition-colors hover:bg-page disabled:opacity-40">Suivant</button>
                </div>
            </div>
        @endif
    </x-card>
</div>
