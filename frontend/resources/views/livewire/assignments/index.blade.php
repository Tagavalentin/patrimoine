<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Affectations</h2>
            <p class="mt-1 text-sm text-muted">{{ $total }} affectation(s)</p>
        </div>
        @if ($canManage)
            <x-button variant="gold" href="{{ route('assignments.create') }}">+ Nouvelle affectation</x-button>
        @endif
    </div>

    @if ($notice)
        <x-card class="mt-6 border-brand bg-brand-soft/50">
            <p class="text-sm font-semibold text-brand-dark">{{ $notice }}</p>
        </x-card>
    @endif
    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
            <x-button variant="neutral" wire:click="$refresh" class="mt-3">Réessayer</x-button>
        </x-card>
    @endif

    {{-- Filtres --}}
    <x-card class="mt-6">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="activeFilter" class="mb-1.5 block text-[13px] font-semibold text-ink">Période</label>
                <select id="activeFilter" wire:model.live="activeFilter"
                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">Toutes</option>
                    <option value="active">Actives</option>
                    <option value="closed">Clôturées</option>
                </select>
            </div>
            <div>
                <label for="departmentId" class="mb-1.5 block text-[13px] font-semibold text-ink">Service</label>
                <select id="departmentId" wire:model.live="departmentId"
                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">Tous</option>
                    @foreach ($departmentsForFilter as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    {{-- Tableau --}}
    <x-card class="mt-6 overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                    <tr>
                        <th class="px-5 py-3.5">Bien</th>
                        <th class="px-5 py-3.5">Service</th>
                        <th class="px-5 py-3.5">Responsable</th>
                        <th class="px-5 py-3.5">Début</th>
                        <th class="px-5 py-3.5">Fin</th>
                        <th class="px-5 py-3.5">Statut</th>
                        @if ($canManage) <th class="px-5 py-3.5 text-right">Actions</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($assignments as $a)
                        <tr class="transition-colors hover:bg-page" wire:key="a-{{ $a['id'] }}">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('assets.show', $a['asset']['id'] ?? 0) }}" class="font-semibold text-brand hover:underline">
                                    {{ $a['asset']['designation'] ?? '—' }}
                                </a>
                                <div class="font-mono text-xs text-muted">{{ $a['asset']['code'] ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $a['department']['name'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-muted">
                                {{ $a['responsible']['name'] ?? '—' }}
                                @if (! empty($a['responsible']['email']))
                                    <div class="text-xs text-muted/70">{{ $a['responsible']['email'] }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $a['start_date'] ? \Illuminate\Support\Carbon::parse($a['start_date'])->format('d/m/Y') : '—' }}</td>
                            <td class="px-5 py-3.5 text-muted">{{ $a['end_date'] ? \Illuminate\Support\Carbon::parse($a['end_date'])->format('d/m/Y') : '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if ($a['is_active'] ?? false)
                                    <x-badge variant="assigned">Active</x-badge>
                                @else
                                    <x-badge variant="neutral">Clôturée</x-badge>
                                @endif
                            </td>
                            @if ($canManage)
                                <td class="px-5 py-3.5 text-right">
                                    @if (($a['is_active'] ?? false) && $actionAssignmentId !== $a['id'])
                                        <button type="button" wire:click="beginAction({{ $a['id'] }}, 'transfer', {{ $a['department']['id'] ?? 'null' }})"
                                                class="mr-2 inline-flex min-h-11 items-center rounded-xl border-[1.5px] border-brand px-4 text-sm font-bold text-brand hover:bg-brand-soft">
                                            Transférer
                                        </button>
                                        <button type="button" wire:click="beginAction({{ $a['id'] }}, 'return')"
                                                class="inline-flex min-h-11 items-center rounded-xl border-[1.5px] border-line-strong px-4 text-sm font-bold text-ink hover:bg-page">
                                            Restituer
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        {{-- Panneau d'action --}}
                        @if ($actionAssignmentId === $a['id'])
                            <tr wire:key="action-{{ $a['id'] }}" class="bg-brand-soft/40">
                                <td colspan="{{ $canManage ? 7 : 6 }}" class="px-5 py-5">
                                    <div class="flex flex-wrap items-end gap-3">
                                        <p class="w-full text-sm font-bold text-ink">
                                            {{ $actionType === 'transfer' ? 'Transférer le bien' : 'Restituer le bien au parc' }}
                                            — {{ $a['asset']['designation'] ?? '' }}
                                        </p>
                                        @if ($actionType === 'transfer')
                                            <div>
                                                <label class="mb-1.5 block text-[13px] font-semibold text-ink">Service de destination *</label>
                                                <select wire:model="targetDepartmentId"
                                                        class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                                                    <option value="">— Choisir —</option>
                                                    @foreach ($departmentsForFilter as $d)
                                                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                @error('targetDepartmentId') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[13px] font-semibold text-ink">Nouveau responsable</label>
                                                <input type="text" wire:model="responsibleName" placeholder="Nom"
                                                       class="h-11 rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none">
                                            </div>
                                        @endif
                                        <div class="min-w-56 flex-1">
                                            <label class="mb-1.5 block text-[13px] font-semibold text-ink">Motif</label>
                                            <input type="text" wire:model="reason" placeholder="Optionnel"
                                                   class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none">
                                        </div>
                                        <x-button variant="primary" wire:click="submitAction" class="px-5">Confirmer</x-button>
                                        <x-button variant="neutral" wire:click="cancelAction" class="px-5">Annuler</x-button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="px-5 py-10 text-center text-muted">Aucune affectation trouvée.</td></tr>
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
