<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tableau de bord') — Gestion du Patrimoine</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page font-sans text-ink antialiased">
    <div class="flex min-h-screen">
        <x-sidebar />

        <main class="flex min-w-0 flex-1 flex-col gap-6 p-6 pb-24 sm:p-8 lg:p-10 lg:pb-10">
            <x-topbar />
            {{ $slot }}
        </main>
    </div>

    <x-bottom-nav />
</body>
</html>
