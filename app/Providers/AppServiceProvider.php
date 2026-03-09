<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use MatanYadaev\EloquentSpatial\EloquentSpatial;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use Illuminate\Support\Facades\URL;
use App\Models\Venta;
use App\Observers\VentaObserver;
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
        Venta::observe(VentaObserver::class);
        URL::forceScheme('https');

        //
        // Para Scramble
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        // Para Scramble Production
        Gate::define('viewApiDocs', function ($user = null) {
            return true;
        });

        // Para Eloquent Spatial
        EloquentSpatial::setDefaultSrid(Srid::WGS84);
    }
}
