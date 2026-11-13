<div>
    <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
    @endif
    @if ($notice)
        <x-card class="mt-6 border-brand bg-brand-soft/50">
            <p class="text-sm font-semibold text-brand-dark">{{ $notice }}</p>
        </x-card>
    @endif

    @if ($request)
        @php
            $stLabel = $presenter::maintenance($status)['label'];
            $prLabel = $priorityPresenter::get($request['priority'] ?? null)['label'];
        @endphp

        {{-- En-tête --}}
        <x-card class="mt-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-mono text-xs font-semibold text-muted">{{ $request['reference'] }}</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-ink">{{ $request['asset']['designation'] ?? 'Bien' }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ $request['asset']['code'] ?? '' }}
                        — demandé par {{ $request['requested_by']['name'] ?? '—' }}
                        le {{ ! empty($request['created_at']) ? \Illuminate\Support\Carbon::parse($request['created_at'])->format('d/m/Y') : '—' }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <x-badge :variant="$status ?? 'neutral'" class="px-3.5 py-2 text-sm">{{ $stLabel }}</x-badge>
                    <x-badge :variant="$request['priority'] ?? 'neutral'">Priorité {{ $prLabel }}</x-badge>
                </div>
            </div>
        </x-card>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Description + planning --}}
            <div class="space-y-6 lg:col-span-2">
                <x-card>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Problème signalé</h3>
                    <p class="mt-3 whitespace-pre-line text-sm text-ink">{{ $request['description'] }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-4 border-t border-line pt-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-muted">Planifiée le</dt><dd class="mt-0.5 font-semibold text-ink">{{ ($request['scheduled_date'] ?? null) ? \Illuminate\Support\Carbon::parse($request['scheduled_date'])->format('d/m/Y') : '—' }}</dd></div>
                        <div><dt class="text-muted">Démarrée le</dt><dd class="mt-0.5 font-semibold text-ink">{{ ($request['started_at'] ?? null) ? \Illuminate\Support\Carbon::parse($request['started_at'])->format('d/m/Y H:i') : '—' }}</dd></div>
                        <div><dt class="text-muted">Terminée le</dt><dd class="mt-0.5 font-semibold text-ink">{{ ($request['completed_at'] ?? null) ? \Illuminate\Support\Carbon::parse($request['completed_at'])->format('d/m/Y H:i') : '—' }}</dd></div>
                        <div><dt class="text-muted">Technicien</dt><dd class="mt-0.5 font-semibold text-ink">{{ $request['assigned_to_name'] ?? '—' }}</dd></div>
                        <div><dt class="text-muted">Coût total</dt><dd class="mt-0.5 font-semibold text-ink">{{ number_format((float) ($request['total_cost'] ?? 0), 0, ',', ' ') }} FCFA</dd></div>
                    </dl>
                </x-card>

                {{-- Interventions --}}
                <x-card class="overflow-hidden p-0">
                    <div class="border-b border-line px-7 py-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Interventions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line text-sm">
                            <thead class="bg-table-head text-left text-[12.5px] font-semibold text-muted">
                                <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Technicien</th><th class="px-5 py-3">Description</th><th class="px-5 py-3 text-right">Coût</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @forelse (($request['interventions'] ?? []) as $i)
                                    <tr class="hover:bg-page">
                                        <td class="px-5 py-3.5 text-muted">{{ $i['performed_at'] ? \Illuminate\Support\Carbon::parse($i['performed_at'])->format('d/m/Y') : '—' }}</td>
                                        <td class="px-5 py-3.5 text-muted">{{ $i['technician_name'] ?? '—' }}</td>
                                        <td class="px-5 py-3.5 text-ink">{{ $i['description'] }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-ink">{{ number_format((float) $i['cost'], 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-muted">Aucune intervention enregistrée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- Actions --}}
            <div class="space-y-6">
                @if ($canManage)
                    @if ($status === 'requested')
                        <x-card>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Planifier</h3>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label for="scheduledDate" class="mb-1.5 block text-[13px] font-semibold text-ink">Date planifiée</label>
                                    <input id="scheduledDate" type="date" wire:model="scheduledDate" min="{{ now()->toDateString() }}"
                                           class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                                    @error('scheduledDate') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="technician" class="mb-1.5 block text-[13px] font-semibold text-ink">Technicien (optionnel)</label>
                                    <input id="technician" type="text" wire:model="technicianName" placeholder="Nom du technicien"
                                           class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none">
                                </div>
                                <x-button variant="primary" wire:click="schedule" wire:loading.attr="disabled" class="w-full">Planifier l'intervention</x-button>
                            </div>
                        </x-card>
                    @endif

                    @if (in_array($status, ['requested', 'scheduled'], true))
                        <x-card>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Démarrer</h3>
                            <p class="mt-2 text-xs text-muted">Le bien passera en maintenance.</p>
                            <x-button variant="gold" wire:click="start" wire:loading.attr="disabled" class="mt-4 w-full">Démarrer les travaux</x-button>
                        </x-card>
                    @endif

                    @if ($status === 'in_progress')
                        <x-card>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Ajouter une intervention</h3>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label for="intDesc" class="mb-1.5 block text-[13px] font-semibold text-ink">Description *</label>
                                    <textarea id="intDesc" wire:model="interventionDescription" rows="3"
                                              class="w-full rounded-xl border border-line-strong bg-white px-3.5 py-3 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none"></textarea>
                                    @error('interventionDescription') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="intCost" class="mb-1.5 block text-[13px] font-semibold text-ink">Coût (FCFA)</label>
                                        <input id="intCost" type="number" min="0" wire:model="interventionCost"
                                               class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                                    </div>
                                    <div>
                                        <label for="performedAt" class="mb-1.5 block text-[13px] font-semibold text-ink">Date *</label>
                                        <input id="performedAt" type="date" wire:model="performedAt" max="{{ now()->toDateString() }}"
                                               class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                                        @error('performedAt') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <x-button variant="outline" wire:click="addIntervention" wire:loading.attr="disabled" class="w-full">Enregistrer l'intervention</x-button>
                            </div>
                        </x-card>

                        <x-card>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Terminer</h3>
                            <p class="mt-2 text-xs text-muted">Le statut antérieur du bien sera restauré.</p>
                            <x-button variant="primary" wire:click="complete" wire:confirm="Terminer cette demande de maintenance ?" wire:loading.attr="disabled" class="mt-4 w-full">Marquer comme terminée</x-button>
                        </x-card>
                    @endif
                @else
                    <x-card>
                        <p class="text-sm text-muted">Consultation seule — votre rôle ne permet pas de modifier le workflow.</p>
                    </x-card>
                @endif
            </div>
        </div>
    @endif
</div>
