<div>
    <a href="{{ route('maintenance.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>
    <h1 class="mt-2 text-2xl font-semibold text-slate-800">Nouvelle demande de maintenance</h1>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <form wire:submit="save" class="mt-6 max-w-2xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div>
            <label for="assetId" class="block text-sm font-medium text-slate-700">Bien concerné *</label>
            <select id="assetId" wire:model="assetId"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">— Sélectionner un bien —</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset['id'] }}">{{ $asset['code'] }} — {{ $asset['designation'] }}</option>
                @endforeach
            </select>
            @error('assetId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="priority" class="block text-sm font-medium text-slate-700">Priorité *</label>
            <select id="priority" wire:model="priority"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('priority') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700">Description du problème *</label>
            <textarea id="description" wire:model="description" rows="5"
                      placeholder="Décrivez le dysfonctionnement constaté (10 caractères minimum)…"
                      class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('maintenance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Annuler</a>
            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Créer la demande</span>
                <span wire:loading wire:target="save">Envoi…</span>
            </button>
        </div>
    </form>
</div>
