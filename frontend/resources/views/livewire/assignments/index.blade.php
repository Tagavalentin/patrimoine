<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Affectations</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} affectation(s)</p>
        </div>
        <a href="{{ route('assignments.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Nouvelle affectation
        </a>
    </div>

    @if ($notice)
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
    @endif
    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    {{-- Filtres --}}
    <div class="mt-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div>
            <label for="activeFilter" class="block text-xs font-medium text-slate-500">Période</label>
            <select id="activeFilter" wire:model.live="activeFilter"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                <option value="active">Actives</option>
                <option value="closed">Clôturées</option>
            </select>
        </div>
        <div>
            <label for="departmentId" class="block text-xs font-medium text-slate-500">Service</label>
            <select id="departmentId" wire:model.live="departmentId"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($departmentsForFilter as $d)
                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Bien</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Début</th>
                    <th class="px-4 py-3">Fin</th>
                    <th class="px-4 py-3">Statut</th>
                    @if ($canManage) <th class="px-4 py-3 text-right">Actions</th> @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments as $a)
                    <tr class="hover:bg-slate-50" wire:key="a-{{ $a['id'] }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('assets.show', $a['asset']['id'] ?? 0) }}" class="font-medium text-indigo-700 hover:underline">
                                {{ $a['asset']['designation'] ?? '—' }}
                            </a>
                            <div class="font-mono text-xs text-slate-500">{{ $a['asset']['code'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $a['department']['name'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $a['responsible']['name'] ?? '—' }}
                            @if (! empty($a['responsible']['email']))
                                <div class="text-xs text-slate-400">{{ $a['responsible']['email'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $a['start_date'] ? \Illuminate\Support\Carbon::parse($a['start_date'])->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $a['end_date'] ? \Illuminate\Support\Carbon::parse($a['end_date'])->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($a['is_active'] ?? false)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Clôturée</span>
                            @endif
                        </td>
                        @if ($canManage)
                            <td class="px-4 py-3 text-right">
                                @if (($a['is_active'] ?? false) && $actionAssignmentId !== $a['id'])
                                    <button wire:click="beginAction({{ $a['id'] }}, 'transfer', {{ $a['department']['id'] ?? 'null' }})"
                                            class="mr-2 rounded-lg border border-indigo-300 px-3 py-1 text-xs text-indigo-700 hover:bg-indigo-50">
                                        Transférer
                                    </button>
                                    <button wire:click="beginAction({{ $a['id'] }}, 'return')"
                                            class="rounded-lg border border-slate-300 px-3 py-1 text-xs text-slate-600 hover:bg-slate-50">
                                        Restituer
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                    {{-- Panneau d'action --}}
                    @if ($actionAssignmentId === $a['id'])
                        <tr wire:key="action-{{ $a['id'] }}">
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="bg-indigo-50/50 px-4 py-4">
                                <div class="flex flex-wrap items-end gap-3">
                                    <p class="w-full text-sm font-medium text-slate-700">
                                        {{ $actionType === 'transfer' ? 'Transférer le bien' : 'Restituer le bien au parc' }}
                                        — {{ $a['asset']['designation'] ?? '' }}
                                    </p>
                                    @if ($actionType === 'transfer')
                                        <div>
                                            <label class="block text-xs font-medium text-slate-500">Service de destination *</label>
                                            <select wire:model="targetDepartmentId"
                                                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                                <option value="">— Choisir —</option>
                                                @foreach ($departmentsForFilter as $d)
                                                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('targetDepartmentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-500">Nouveau responsable</label>
                                            <input type="text" wire:model="responsibleName" placeholder="Nom"
                                                   class="mt-1 block rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                        </div>
                                    @endif
                                    <div class="min-w-56 flex-1">
                                        <label class="block text-xs font-medium text-slate-500">Motif</label>
                                        <input type="text" wire:model="reason" placeholder="Optionnel"
                                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                    </div>
                                    <button wire:click="submitAction"
                                            class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                        Confirmer
                                    </button>
                                    <button wire:click="cancelAction"
                                            class="rounded-lg border border-slate-300 px-4 py-1.5 text-sm hover:bg-slate-50">
                                        Annuler
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-8 text-center text-slate-500">Aucune affectation trouvée.</td></tr>
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
