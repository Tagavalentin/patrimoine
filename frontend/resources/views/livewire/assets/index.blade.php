<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Biens</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} bien(s) dans le patrimoine</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="mt-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="min-w-56 flex-1">
            <label for="search" class="block text-xs font-medium text-slate-500">Recherche</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Code ou désignation…"
                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label for="status" class="block text-xs font-medium text-slate-500">Statut</label>
            <select id="status" wire:model.live="status"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <option value="">Tous</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category" class="block text-xs font-medium text-slate-500">Catégorie</label>
            <select id="category" wire:model.live="categoryId"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <option value="">Toutes</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    {{-- Tableau --}}
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Désignation</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Localisation</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Valeur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assets as $asset)
                    @php $s = $presenter::asset($asset['status'] ?? null); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $asset['code'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('assets.show', $asset['id']) }}" class="font-medium text-indigo-700 hover:underline">
                                {{ $asset['designation'] }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $asset['category']['name'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $asset['location']['name'] ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $s['class'] }}">{{ $s['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">
                            {{ isset($asset['acquisition_value']) ? number_format((float) $asset['acquisition_value'], 0, ',', ' ') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun bien trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
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
