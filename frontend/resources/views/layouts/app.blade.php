<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tableau de bord') — Gestion du Patrimoine</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">
        {{-- Barre latérale --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-slate-900 text-slate-200 transition-transform lg:static lg:translate-x-0"
               :class="{ 'translate-x-0': sidebarOpen }">
            <div class="flex h-16 items-center px-6 text-lg font-semibold text-white">
                Gestion du Patrimoine
            </div>
            <nav class="mt-4 space-y-1 px-3 text-sm">
                <a href="{{ route('dashboard') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-slate-700 font-medium text-white' : 'hover:bg-slate-800' }}">
                    Tableau de bord
                </a>
                <a href="{{ route('assets.index') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('assets.*') ? 'bg-slate-700 font-medium text-white' : 'hover:bg-slate-800' }}">
                    Biens
                </a>
                {{-- Entrées futures — branchées aux écrans des étapes suivantes --}}
                <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-slate-500" title="Étape suivante">Biens</span>
                <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-slate-500" title="Étape suivante">Affectations</span>
                <a href="{{ route('maintenance.index') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('maintenance.*') ? 'bg-slate-700 font-medium text-white' : 'hover:bg-slate-800' }}">
                    Maintenance
                </a>
                <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-slate-500" title="Étape suivante">Inventaires</span>
                <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-slate-500" title="Étape suivante">Rapports</span>
            </nav>
        </aside>

        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Barre supérieure --}}
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">
                <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="ml-auto flex items-center gap-4">
                    <div class="text-right text-sm">
                        <div class="font-medium">{{ session('jwt_user.name', 'Utilisateur') }}</div>
                        <div class="text-slate-500">{{ session('jwt_user.role_name', session('jwt_user.role', '')) }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
