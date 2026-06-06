<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Azienda;
use App\Observers\AziendaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

protected $policies = [
    \App\Models\Documento::class => \App\Policies\DocumentoPolicy::class,
];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
 {
        Azienda::observe(AziendaObserver::class);
    }
}
