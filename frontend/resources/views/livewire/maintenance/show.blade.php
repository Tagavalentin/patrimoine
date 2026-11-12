<div>
    <a href="{{ route('maintenance.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif
    @if ($notice)
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
    @endif

    @if ($request)
        @php
            $st = $presenter::maintenance($status);
            $pr = $priorityPresenter::get($request['priority'] ?? null);
        @endphp

        {{-- En-tête --}}
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div>
                <p class="font-mono text-xs text-slate-500">{{ $request['reference'] }}</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-800">{{ $request['asset']['designation'] ?? 'Bien' }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $request['asset']['code'] ?? '' }}
                    — demandé par {{ $request['requested_by']['name'] ?? '—' }}
                    le {{ ! empty($request['created_at']) ? \Illuminate\Support\Carbon::parse($request['created_at'])->format('d/m/Y') : '—' }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $st['class'] }}">{{ $st['label'] }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $pr['class'] }}">Priorité {{ $pr['label'] }}</span>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Description + planning --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Problème signalé</h2>
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $request['description'] }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-slate-500">Planifiée le</dt><dd class="font-medium">{{ ($request['scheduled_date'] ?? null) ? \Illuminate\Support\Carbon::parse($request['scheduled_date'])->format('d/m/Y') : '—' }}</dd></div>
                        <div><dt class="text-slate-500">Démarrée le</dt><dd class="font-medium">{{ ($request['started_at'] ?? null) ? \Illuminate\Support\Carbon::parse($request['started_at'])->format('d/m/Y H:i') : '—' }}</dd></div>
                        <div><dt class="text-slate-500">Terminée le</dt><dd class="font-medium">{{ ($request['completed_at'] ?? null) ? \Illuminate\Support\Carbon::parse($request['completed_at'])->format('d/m/Y H:i') : '—' }}</dd></div>
                        <div><dt class="text-slate-500">Technicien</dt><dd class="font-medium">{{ $request['assigned_to_name'] ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Coût total</dt><dd class="font-medium">{{ number_format((float) ($request['total_cost'] ?? 0), 0, ',', ' ') }} FCFA</dd></div>
                    </dl>
                </div>

                {{-- Interventions --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Interventions</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr><th class="py-2 pr-4">Date</th><th class="py-2 pr-4">Technicien</th><th class="py-2 pr-4">Description</th><th class="py-2 text-right">Coût</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse (($request['interventions'] ?? []) as $i)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $i['performed_at'] ? \Illuminate\Support\Carbon::parse($i['performed_at'])->format('d/m/Y') : '—' }}</td>
                                        <td class="py-2 pr-4">{{ $i['technician_name'] ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-slate-600">{{ $i['description'] }}</td>
                                        <td class="py-2 text-right">{{ number_format((float) $i['cost'], 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-slate-500">Aucune intervention enregistrée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-6">
                @if ($canManage)
                    @if ($status === 'requested')
                        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Planifier</h2>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label for="scheduledDate" class="block text-xs font-medium text-slate-500">Date planifiée</label>
                                    <input id="scheduledDate" type="date" wire:model="scheduledDate" min="{{ now()->toDateString() }}"
                                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    @error('scheduledDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="technician" class="block text-xs font-medium text-slate-500">Technicien (optionnel)</label>
                                    <input id="technician" type="text" wire:model="technicianName" placeholder="Nom du technicien"
                                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <button wire:click="schedule" wire:loading.attr="disabled"
                                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                    Planifier l'intervention
                                </button>
                            </div>
                        </div>
                    @endif

                    @if (in_array($status, ['requested', 'scheduled'], true))
                        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Démarrer</h2>
                            <p class="mt-2 text-xs text-slate-500">Le bien passera en maintenance.</p>
                            <button wire:click="start" wire:loading.attr="disabled"
                                    class="mt-4 w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50">
                                Démarrer les travaux
                            </button>
                        </div>
                    @endif

                    @if ($status === 'in_progress')
                        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Ajouter une intervention</h2>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label for="intDesc" class="block text-xs font-medium text-slate-500">Description *</label>
                                    <textarea id="intDesc" wire:model="interventionDescription" rows="3"
                                              class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                                    @error('interventionDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="intCost" class="block text-xs font-medium text-slate-500">Coût (FCFA)</label>
                                        <input id="intCost" type="number" min="0" wire:model="interventionCost"
                                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label for="performedAt" class="block text-xs font-medium text-slate-500">Date *</label>
                                        <input id="performedAt" type="date" wire:model="performedAt" max="{{ now()->toDateString() }}"
                                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        @error('performedAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <button wire:click="addIntervention" wire:loading.attr="disabled"
                                        class="w-full rounded-lg border border-indigo-600 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 disabled:opacity-50">
                                    Enregistrer l'intervention
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Terminer</h2>
                            <p class="mt-2 text-xs text-slate-500">Le statut antérieur du bien sera restauré.</p>
                            <button wire:click="complete" wire:confirm="Terminer cette demande de maintenance ?" wire:loading.attr="disabled"
                                    class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                                Marquer comme terminée
                            </button>
                        </div>
                    @endif
                @else
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <p class="text-sm text-slate-500">Consultation seule — votre rôle ne permet pas de modifier le workflow.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
