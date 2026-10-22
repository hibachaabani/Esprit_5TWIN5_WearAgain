<?php

namespace App\Providers;

use App\Models\Transformation;
use App\Models\User;
use App\Policies\TransformationPolicy;
use Illuminate\Support\Facades\Blade;
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
        // Accès au back-office réservé aux administrateurs (rôle "admin").
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Cycle de vie des transformations : qui (atelier / client) peut faire quoi.
        Gate::policy(Transformation::class, TransformationPolicy::class);
        // @particulier … @endparticulier : actions d'un particulier, montrées aussi aux visiteurs
        // (qui seront invités à se connecter), mais pas aux associations, ateliers et admins.
        Blade::if('particulier', fn () => ! auth()->check() || auth()->user()->estParticulier());
    }
}
