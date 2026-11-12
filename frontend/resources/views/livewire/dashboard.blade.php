<div>
    <h1 class="text-2xl font-semibold text-slate-800">Tableau de bord</h1>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        {{-- Carte utilisateur --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Utilisateur connecté</h2>
            @if ($me)
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Nom</dt><dd class="font-medium">{{ $me['name'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">E-mail</dt><dd class="font-medium">{{ $me['email'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Rôle</dt><dd class="font-medium">{{ $me['role']['name'] ?? '—' }}</dd></div>
                </dl>
            @else
                <p class="mt-4 text-sm text-slate-500">Profil indisponible.</p>
            @endif
        </div>

        {{-- Carte socle --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Patrimoine</h2>
            <p class="mt-4 text-sm text-slate-500">
                Les indicateurs du patrimoine (biens, maintenance, inventaires) seront affichés ici
                aux prochaines étapes, à partir de l'API de reporting.
            </p>
        </div>
    </div>
</div>
