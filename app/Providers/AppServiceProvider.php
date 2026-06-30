<?php

namespace App\Providers;

use App\Listeners\RegistraEsitoEmail;
use App\Models\Azienda;
use App\Models\Documento;
use App\Observers\AziendaObserver;
use App\Policies\DocumentoPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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

    protected $policies = [
        Documento::class => DocumentoPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        Azienda::observe(AziendaObserver::class);

        Event::subscribe(RegistraEsitoEmail::class);
    }
}
