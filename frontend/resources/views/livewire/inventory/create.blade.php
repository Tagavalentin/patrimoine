<div>
    <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>
    <h2 class="mt-2 text-2xl font-extrabold text-ink">Nouvelle campagne d'inventaire</h2>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
    @endif

    <x-card class="mt-6 max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <x-input wire:model="name" label="Nom de la campagne *" placeholder="Ex. Inventaire annuel 2026" />
            @error('name') <p class="-mt-3 text-xs font-medium text-error">{{ $message }}</p> @enderror

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="departmentId" class="mb-1.5 block text-[13px] font-semibold text-ink">Département (optionnel)</label>
                    <select id="departmentId" wire:model="departmentId"
                            class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                        <option value="">— Patrimoine complet —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="locationId" class="mb-1.5 block text-[13px] font-semibold text-ink">Localisation (optionnel)</label>
                    <select id="locationId" wire:model="locationId"
                            class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                        <option value="">— Toutes —</option>
                        @foreach ($locations as $l)
                            <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="text-xs text-muted">Les lignes de la campagne sont générées à la création à partir des biens correspondants.</p>

            <div class="flex justify-end gap-3 border-t border-line pt-5">
                <x-button variant="neutral" href="{{ route('inventory.index') }}">Annuler</x-button>
                <x-button variant="primary" type="submit" wire:loading.attr="disabled">Créer la campagne</x-button>
            </div>
        </form>
    </x-card>
</div>
