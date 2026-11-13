<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Gestion du Patrimoine</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page font-sans text-ink antialiased">
    <div class="flex min-h-screen">
        {{-- Panneau de marque --}}
        <aside class="relative hidden w-[46%] flex-col justify-between overflow-hidden bg-sidebar p-12 text-white lg:flex">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold text-sidebar">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"/></svg>
                </span>
                <div>
                    <p class="text-lg font-extrabold leading-tight">Patrimoine</p>
                    <p class="text-xs text-sidebar-sub">Gestion des biens de l'État</p>
                </div>
            </div>

            <div class="relative">
                <svg class="absolute -right-10 -top-10 h-72 w-72" viewBox="0 0 320 320" fill="none" stroke="#1B8E76" stroke-width="2" aria-hidden="true">
                    <circle cx="160" cy="160" r="60"></circle>
                    <circle cx="160" cy="160" r="100"></circle>
                    <circle cx="160" cy="160" r="140"></circle>
                </svg>
                <h1 class="relative text-4xl font-extrabold leading-tight">Pilotez le patrimoine de l'État en toute transparence.</h1>
                <p class="relative mt-4 max-w-md text-sm leading-relaxed text-sidebar-text">
                    Biens, affectations, maintenance et inventaires : une vision consolidée et fiable du patrimoine public.
                </p>
            </div>

            <p class="text-xs text-sidebar-sub">© {{ date('Y') }} — Gestion du Patrimoine de l'État</p>
        </aside>

        {{-- Formulaire --}}
        <main class="flex flex-1 items-center justify-center p-6">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold text-sidebar">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"/></svg>
                        </span>
                        <div>
                            <p class="text-lg font-extrabold leading-tight">Patrimoine</p>
                            <p class="text-xs text-muted">Gestion des biens de l'État</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-extrabold text-ink">Connexion</h2>
                <p class="mt-1 text-sm text-muted">Connectez-vous pour continuer</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-decommissioned-bg bg-decommissioned-bg/40 px-4 py-3 text-sm font-semibold text-decommissioned-text">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="mt-6 space-y-4">
                    @csrf
                    <x-input name="email" id="email" type="email" label="Adresse e-mail" required autofocus value="{{ old('email') }}" autocomplete="email" />
                    <x-input name="password" id="password" type="password" label="Mot de passe" required autocomplete="current-password" />
                    <x-button variant="primary" type="submit" class="mt-2 w-full">Se connecter</x-button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
