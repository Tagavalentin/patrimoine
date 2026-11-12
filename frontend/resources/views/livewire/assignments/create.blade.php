<div>
    <a href="{{ route('assignments.index') }}" class="text-sm text-indigo-700 hover:underline">← Retour à la liste</a>
    <h1 class="mt-2 text-2xl font-semibold text-slate-800">Nouvelle affectation</h1>
    <p class="mt-1 text-sm text-slate-500">Seuls les biens au statut « Acquis » peuvent être affectés.</p>

    @if ($error)
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <form wire:submit="save" class="mt-6 max-w-2xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div>
            <label for="assetId" class="block text-sm font-medium text-slate-700">Bien à affecter *</label>
            <select id="assetId" wire:model="assetId"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Sélectionner un bien acquis —</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset['id'] }}">{{ $asset['code'] }} — {{ $asset['designation'] }}</option>
                @endforeach
            </select>
            @error('assetId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @if (empty($assets))
                <p class="mt-1 text-xs text-amber-600">Aucun bien disponible à l'affectation.</p>
            @endif
        </div>

        <div>
            <label for="departmentId" class="block text-sm font-medium text-slate-700">Service destinataire *</label>
            <select id="departmentId" wire:model="departmentId"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Choisir un service —</option>
                @foreach ($departments as $d)
                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </select>
            @error('departmentId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="responsibleName" class="block text-sm font-medium text-slate-700">Responsable (nom)</label>
                <input id="responsibleName" type="text" wire:model="responsibleName" placeholder="Nom du responsable"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="responsibleEmail" class="block text-sm font-medium text-slate-700">E-mail</label>
                <input id="responsibleEmail" type="email" wire:model="responsibleEmail"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('responsibleEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="startDate" class="block text-sm font-medium text-slate-700">Date de début</label>
                <input id="startDate" type="date" wire:model="startDate"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700">Motif</label>
                <input id="reason" type="text" wire:model="reason" placeholder="Optionnel"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('assignments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Annuler</a>
            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                Affecter le bien
            </button>
        </div>
    </form>
</div>
