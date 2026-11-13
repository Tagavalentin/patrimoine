@php
    // Barre de navigation mobile — même filtrage par rôle que la sidebar
    $role = session('jwt_user.role');
    $items = [
        ['label' => 'Accueil', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'home', 'roles' => ['admin', 'asset_manager', 'service_head', 'accountant', 'auditor']],
        ['label' => 'Biens', 'route' => 'assets.index', 'pattern' => 'assets.*', 'icon' => 'package', 'roles' => ['admin', 'asset_manager', 'service_head', 'accountant', 'auditor']],
        ['label' => 'Maintenance', 'route' => 'maintenance.index', 'pattern' => 'maintenance.*', 'icon' => 'wrench', 'roles' => ['admin', 'asset_manager', 'service_head']],
        ['label' => 'Rapports', 'route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'chart', 'roles' => ['admin', 'accountant', 'auditor']],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 flex h-[76px] items-stretch border-t border-line bg-white lg:hidden" aria-label="Navigation mobile">
    @foreach ($items as $item)
        @if (in_array($role, $item['roles'], true))
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               @if ($active) aria-current="page" @endif
               class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 text-[12px] font-semibold {{ $active ? 'text-brand' : 'text-muted' }}">
                <x-icon name="{{ $item['icon'] }}" class="h-[22px] w-[22px]" />
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
