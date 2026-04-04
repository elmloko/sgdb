<?php

namespace App\Providers;

use App\Models\Bug;
use App\Models\Proyecto;
use App\Observers\BugObserver;
use App\Policies\BugPolicy;
use App\Policies\ProyectoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Observers
        Bug::observe(BugObserver::class);

        // Registro de Policies (equivalente a AuthServiceProvider en Laravel ≤10)
        Gate::policy(Bug::class, BugPolicy::class);
        Gate::policy(Proyecto::class, ProyectoPolicy::class);
    }
}
