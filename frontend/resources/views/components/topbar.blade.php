@php
    $name = session('jwt_user.name', 'Utilisateur');
    $firstName = trim(explode(' ', $name)[0]);
    $today = now()->locale('fr')->translatedFormat('l j F Y');
@endphp

<header class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="text-sm font-medium text-muted">{{ $today }}</p>
        <h1 class="mt-1 text-[26px] font-extrabold leading-tight text-ink lg:text-[32px]">Bonjour, {{ $firstName }}</h1>
    </div>

    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('assets.index') }}" role="search" class="hidden sm:block">
            <label for="global-search" class="sr-only">Rechercher un bien</label>
            <input id="global-search" type="search" name="search" placeholder="Rechercher un bien…"
                   class="h-11 w-[280px] max-w-full rounded-full border border-line-strong bg-white px-[18px] text-sm font-medium text-ink placeholder:text-muted/70 focus:border-brand focus:outline-none">
        </form>
        <button type="button" aria-label="Notifications"
                class="flex h-11 w-11 flex-none items-center justify-center rounded-full border border-line-strong bg-white text-ink hover:bg-page">
            <x-icon name="bell" class="h-5 w-5" />
        </button>
        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-gold text-sm font-extrabold text-sidebar lg:hidden" aria-hidden="true">
            {{ collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U' }}
        </span>
    </div>
</header>
