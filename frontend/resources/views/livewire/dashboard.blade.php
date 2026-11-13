<div>
    @if ($error)
        <x-card class="flex flex-col items-start gap-4 border-decommissioned-bg">
            <p class="text-sm font-semibold text-decommissioned-text">{{ $error }}</p>
            <x-button variant="neutral" wire:click="$refresh">Réessayer</x-button>
        </x-card>
    @elseif (! $data)
        {{-- Squelettes de chargement --}}
        <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
            <div class="h-[260px] animate-pulse rounded-3xl bg-line/60"></div>
            <div class="h-[260px] animate-pulse rounded-3xl border border-line bg-white"></div>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="h-44 animate-pulse rounded-3xl border border-line bg-white"></div>
            @endfor
        </div>
    @else
        @php
            $assets = $data['assets'] ?? [];
            $maintenance = $data['maintenance'] ?? [];
            $inventory = $data['inventory'] ?? [];
            $generatedAt = isset($data['generated_at'])
                ? 'Données consolidées en temps réel, générées le '.\Illuminate\Support\Carbon::parse($data['generated_at'])->format('d/m/Y').' à '.\Illuminate\Support\Carbon::parse($data['generated_at'])->format('H:i')
                : null;
            $inProgressCampaigns = ($inventory['latest_status'] ?? null) === 'in_progress' ? 1 : 0;
            $invStatus = $inventory['latest_status'] ?? null;
            $invLabel = $presenter::inventory($invStatus)['label'];
            $invBadgeVariant = match ($invStatus) {
                'in_progress' => 'warn',
                'completed' => 'assigned',
                default => 'neutral',
            };
            $role = session('jwt_user.role');
            $canManageAssets = in_array($role, ['admin', 'asset_manager'], true);
            $canRequestMaintenance = in_array($role, ['admin', 'asset_manager', 'service_head'], true);
        @endphp

        {{-- Rangée 1 : valeur nette + dernière campagne --}}
        <section class="grid gap-5 lg:grid-cols-[2fr_1fr]">
            <x-hero-card
                label="Valeur nette comptable"
                :value="isset($assets['net_book_value']) ? number_format((float) $assets['net_book_value'], 0, ',', ' ') : '—'"
                :generated-at="$generatedAt"
                :chips="[
                    ($assets['total'] ?? 0).' bien'.(($assets['total'] ?? 0) > 1 ? 's' : '').' suivis',
                    ($maintenance['open'] ?? 0).' demande'.(($maintenance['open'] ?? 0) > 1 ? 's' : '').' de maintenance',
                    $inProgressCampaigns.' inventaire'.($inProgressCampaigns > 1 ? 's' : '').' en cours',
                ]"
            />

            <x-card class="flex flex-col gap-2.5">
                <span class="text-sm font-semibold text-muted">Dernière campagne d'inventaire</span>
                <span class="text-[26px] font-extrabold leading-tight text-ink">{{ $inventory['latest_campaign'] ?? '—' }}</span>
                @if ($invStatus)
                    <x-badge :variant="$invBadgeVariant" class="self-start">{{ $invLabel }}</x-badge>
                @endif
                <x-button variant="gold" href="{{ route('inventory.index') }}" class="mt-auto w-full">
                    {{ $invStatus === 'in_progress' ? "Reprendre l'inventaire" : "Voir l'inventaire" }}
                </x-button>
            </x-card>
        </section>

        {{-- Rangée 2 : indicateurs + actions rapides --}}
        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <x-stat-card
                icon="package"
                icon-class="bg-brand-soft text-brand"
                label="Biens suivis"
                :value="$assets['total'] ?? 0"
                :href="route('assets.index')"
                link-label="Voir les biens"
            />

            <x-stat-card
                icon="wrench"
                icon-class="bg-warn-bg text-warn-text"
                label="Demandes de maintenance ouvertes"
                :value="$maintenance['open'] ?? 0"
                :href="route('maintenance.index')"
                link-label="Traiter les demandes"
            />

            @if ($canManageAssets || $canRequestMaintenance)
                <x-card class="flex flex-col gap-2.5">
                    <span class="mb-1 text-sm font-semibold text-muted">Actions rapides</span>
                    @if ($canManageAssets)
                        <x-button variant="primary" href="{{ route('assets.index') }}" class="w-full">Ajouter un bien</x-button>
                        <x-button variant="outline" href="{{ route('assignments.create') }}" class="w-full">Affecter un bien</x-button>
                    @endif
                    @if ($canRequestMaintenance)
                        <x-button variant="neutral" href="{{ route('maintenance.create') }}" class="w-full">Demander une maintenance</x-button>
                    @endif
                </x-card>
            @endif
        </section>
    @endif
</div>
