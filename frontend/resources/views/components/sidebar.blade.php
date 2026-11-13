@php
    $role = session('jwt_user.role');
    $user = session('jwt_user', []);
    $name = $user['name'] ?? 'Utilisateur';
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';

    // Rubriques visibles par rôle (règle de présentation — l'API reste l'autorité)
    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'dashboard', 'roles' => ['admin', 'asset_manager', 'service_head', 'accountant', 'auditor']],
        ['label' => 'Biens', 'route' => 'assets.index', 'pattern' => 'assets.*', 'icon' => 'package', 'roles' => ['admin', 'asset_manager', 'service_head', 'accountant', 'auditor']],
        ['label' => 'Affectations', 'route' => 'assignments.index', 'pattern' => 'assignments.*', 'icon' => 'swap', 'roles' => ['admin', 'asset_manager', 'service_head']],
        ['label' => 'Maintenance', 'route' => 'maintenance.index', 'pattern' => 'maintenance.*', 'icon' => 'wrench', 'roles' => ['admin', 'asset_manager', 'service_head']],
        ['label' => 'Inventaires', 'route' => 'inventory.index', 'pattern' => 'inventory.*', 'icon' => 'clipboard', 'roles' => ['admin', 'asset_manager', 'service_head', 'auditor']],
        ['label' => 'Rapports', 'route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'chart', 'roles' => ['admin', 'accountant', 'auditor']],
    ];
@endphp

<aside class="hidden w-64 flex-none flex-col gap-7 bg-sidebar px-[18px] py-7 lg:flex" aria-label="Navigation principale">
    {{-- Logo --}}
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-1.5">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold text-sidebar">
            <x-icon name="building" class="h-[22px] w-[22px]" />
        </span>
        <span>
            <span class="block text-lg font-extrabold leading-tight text-white">Patrimoine</span>
            <span class="block text-xs text-sidebar-sub">Gestion des biens de l'État</span>
        </span>
    </a>

    {{-- Navigation --}}
    <nav class="flex flex-col gap-1" aria-label="Menu">
        @foreach ($nav as $item)
            @if (in_array($role, $item['roles'], true))
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}"
                   @if ($active) aria-current="page" @endif
                   class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-semibold {{ $active ? 'bg-sidebar-active text-white' : 'text-sidebar-text hover:bg-sidebar-active/60 hover:text-white' }}">
                    <x-icon name="{{ $item['icon'] }}" class="h-5 w-5 {{ $active ? 'text-gold-light' : '' }}" />
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Profil --}}
    <div class="mt-auto flex items-center gap-3 rounded-2xl bg-sidebar-active p-3.5">
        <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-gold text-sm font-extrabold text-sidebar" aria-hidden="true">{{ $initials }}</span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold text-white">{{ $name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[13px] font-semibold text-gold-light hover:underline">Déconnexion</button>
            </form>
        </span>
    </div>
</aside>
