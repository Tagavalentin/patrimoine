<div>
    <a href="{{ route('assignments.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour à la liste
    </a>
    <h2 class="mt-2 text-2xl font-extrabold text-ink">Nouvelle affectation</h2>
    <p class="mt-1 text-sm text-muted">Seuls les biens au statut « Acquis » peuvent être affectés.</p>

    @if ($error)
        <x-card class="mt-6 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
        </x-card>
    @endif

    <x-card class="mt-6 max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="assetId" class="mb-1.5 block text-[13px] font-semibold text-ink">Bien à affecter *</label>
                <select id="assetId" wire:model="assetId"
                        class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">— Sélectionner un bien acquis —</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset['id'] }}">{{ $asset['code'] }} — {{ $asset['designation'] }}</option>
                    @endforeach
                </select>
                @error('assetId') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                @if (empty($assets))
                    <p class="mt-1.5 text-xs font-medium text-warn-text">Aucun bien disponible à l'affectation.</p>
                @endif
            </div>

            <div>
                <label for="departmentId" class="mb-1.5 block text-[13px] font-semibold text-ink">Service destinataire *</label>
                <select id="departmentId" wire:model="departmentId"
                        class="h-11 w-full rounded-xl border border-line-strong bg-white px-3.5 text-sm font-medium text-ink focus:border-brand focus:outline-none">
                    <option value="">— Choisir un service —</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
                @error('departmentId') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input wire:model="responsibleName" label="Responsable (nom)" placeholder="Nom du responsable" />
                </div>
                <div>
                    <x-input wire:model="responsibleEmail" label="E-mail" type="email" placeholder="email@exemple.sn" />
                    @error('responsibleEmail') <p class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input wire:model="startDate" label="Date de début" type="date" />
                <x-input wire:model="reason" label="Motif" placeholder="Optionnel" />
            </div>

            <div class="flex justify-end gap-3 border-t border-line pt-5">
                <x-button variant="neutral" href="{{ route('assignments.index') }}">Annuler</x-button>
                <x-button variant="primary" type="submit" wire:loading.attr="disabled">Affecter le bien</x-button>
            </div>
        </form>
    </x-card>
</div>
