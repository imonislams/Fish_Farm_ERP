<?php

namespace App\Providers;

use App\Models\User;
use App\View\Composers\CompanyComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        $this->registerGateBypass();
        $this->registerBladeDirectives();
        $this->registerViewComposers();
    }

    /**
     * Share company branding with the layouts so the company name/logo is never
     * hard-coded. Applies to the layout + auth layout components.
     */
    private function registerViewComposers(): void
    {
        View::composer([
            'components.layout.app',
            'components.layout.auth',
            'components.layout.error',
            'components.sidebar.sidebar',
            'components.header',
            'auth.login',
        ], CompanyComposer::class);
    }

    /**
     * Super Admin bypass — the ONLY bypass in the system.
     *
     * A Super Admin holds every permission (`*` is expanded by RoleSeeder), but
     * this guarantees a newly added permission is never accidentally denied to
     * them. Every other decision goes through a real permission key.
     *
     * See docs/PERMISSIONS.md §7.
     */
    private function registerGateBypass(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }

    /**
     * Blade directives for permission-aware markup.
     *
     * These control DISPLAY ONLY. They never replace route middleware or policy
     * checks — see docs/PERMISSIONS.md. Backed by the once-per-request
     * permission cache on the User model, so repeated checks are cheap.
     *
     *   @permission('users.create') ... @endpermission
     *   @role('super_admin') ... @endrole
     */
    private function registerBladeDirectives(): void
    {
        Blade::if('permission', function (string ...$permissions): bool {
            $user = auth()->user();

            if (! $user) {
                return false;
            }

            foreach ($permissions as $permission) {
                if (! $user->hasPermission($permission)) {
                    return false;
                }
            }

            return true;
        });

        Blade::if('role', function (string ...$roles): bool {
            $user = auth()->user();

            return $user !== null && $user->hasAnyRole($roles);
        });
    }
}
