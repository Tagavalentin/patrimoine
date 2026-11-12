<?php

namespace App\Providers;

use App\Support\Jwt\JwtUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $role = fn (Authenticatable $user): ?string => $user instanceof JwtUser ? $user->role() : null;

        // Tableau de bord : rôles de pilotage (pas service_head)
        Gate::define('viewDashboard', fn (Authenticatable $user): bool => in_array(
            $role($user), ['admin', 'asset_manager', 'accountant', 'auditor'], true
        ));

        // Exports CSV : gestion + comptabilité
        Gate::define('export', fn (Authenticatable $user): bool => in_array(
            $role($user), ['admin', 'asset_manager', 'accountant'], true
        ));

        // Journal d'audit : admin + auditeur
        Gate::define('viewAudit', fn (Authenticatable $user): bool => in_array(
            $role($user), ['admin', 'auditor'], true
        ));
    }
}
