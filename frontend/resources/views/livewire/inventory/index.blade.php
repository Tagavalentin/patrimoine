<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Inventaires</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} campagne(s)</p>
        </div>
        <a href="{{ route('inventory.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Nouvelle campagne
        </a>
    </div>

    {{-- Filtres --}}
    <div class="mt-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="min-w-56 flex-1">
            <label for="search" class="block text-xs font-medium text-slate-500">Recherche</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Référence ou nom…"
                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="status" class="block text-xs font-medium text-slate-500">Statut</label>
            <select id="status" wire:model.live="status"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Périmètre</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Créée par</th>
                    <th class="px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($campaigns as $c)
                    @php $st = $presenter::inventory($c['status'] ?? null); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $c['reference'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('inventory.show', $c['id']) }}" class="font-medium text-indigo-700 hover:underline">
                                {{ $c['name'] }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $c['scope']['department']['name'] ?? $c['scope']['location']['name'] ?? 'Patrimoine complet' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $st['class'] }}">{{ $st['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $c['created_by']['name'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ ! empty($c['created_at']) ? \Illuminate\Support\Carbon::parse($c['created_at'])->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune campagne trouvée.</td></tr>
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
