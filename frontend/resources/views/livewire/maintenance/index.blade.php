<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Maintenance</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} demande(s)</p>
        </div>
        <a href="{{ route('maintenance.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Nouvelle demande
        </a>
    </div>

    {{-- Filtres --}}
    <div class="mt-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
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
        <div>
            <label for="priority" class="block text-xs font-medium text-slate-500">Priorité</label>
            <select id="priority" wire:model.live="priority"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
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
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Bien</th>
                    <th class="px-4 py-3">Priorité</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Demandé par</th>
                    <th class="px-4 py-3">Créée le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $req)
                    @php
                        $st = $statusPresenter::maintenance($req['status'] ?? null);
                        $pr = $priorityPresenter::get($req['priority'] ?? null);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $req['reference'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('maintenance.show', $req['id']) }}" class="font-medium text-indigo-700 hover:underline">
                                {{ $req['asset']['designation'] ?? '—' }}
                            </a>
                            <div class="text-xs text-slate-500">{{ $req['asset']['code'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $pr['class'] }}">{{ $pr['label'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $st['class'] }}">{{ $st['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $req['requested_by']['name'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ ! empty($req['created_at']) ? \Illuminate\Support\Carbon::parse($req['created_at'])->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune demande trouvée.</td></tr>
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
