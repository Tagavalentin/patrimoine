<div>
    <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>
    <h2 class="mt-2 text-2xl font-extrabold text-ink">Nouvelle demande de maintenance</h2>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
    @endif

    <x-card class="mt-6 max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="assetId" class="mb-1.5 block text-[13px] font-semibold text-ink">Bien concerné *</label>
                <select id="assetId" wire:model="assetId"
                        class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">— Sélectionner un bien —</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset['id'] }}">{{ $asset['code'] }} — {{ $asset['designation'] }}</option>
                    @endforeach
                </select>
                @error('assetId') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="priority" class="mb-1.5 block text-[13px] font-semibold text-ink">Priorité *</label>
                <select id="priority" wire:model="priority"
                        class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="mb-1.5 block text-[13px] font-semibold text-ink">Description du problème *</label>
                <textarea id="description" wire:model="description" rows="5"
                          placeholder="Décrivez le dysfonctionnement constaté (10 caractères minimum)…"
                          class="w-full rounded-xl border border-line-strong bg-white px-3.5 py-3 text-sm font-medium text-ink placeholder:text-muted/60 focus:border-brand focus:outline-none"></textarea>
                @error('description') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-line pt-5">
                <x-button variant="neutral" href="{{ route('maintenance.index') }}">Annuler</x-button>
                <x-button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Créer la demande</span>
                    <span wire:loading wire:target="save">Envoi…</span>
                </x-button>
            </div>
        </form>
    </x-card>
</div>
