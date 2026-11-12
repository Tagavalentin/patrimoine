<div>
    <a href="{{ route('inventory.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>
    <h1 class="mt-2 text-2xl font-semibold text-slate-800">Nouvelle campagne d'inventaire</h1>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <form wire:submit="save" class="mt-6 max-w-2xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Nom de la campagne *</label>
            <input id="name" type="text" wire:model="name"
                   placeholder="Ex. Inventaire annuel 2026"
                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="departmentId" class="block text-sm font-medium text-slate-700">Département (optionnel)</label>
                <select id="departmentId" wire:model="departmentId"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Patrimoine complet —</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="locationId" class="block text-sm font-medium text-slate-700">Localisation (optionnel)</label>
                <select id="locationId" wire:model="locationId"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Toutes —</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="text-xs text-slate-500">Les lignes de la campagne sont générées à la création à partir des biens correspondants.</p>

        <div class="flex justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Annuler</a>
            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                Créer la campagne
            </button>
        </div>
    </form>
</div>
