<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        //
        // Para Scramble
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        // Para Scramble Production
        Gate::define('viewApiDocs', function () {
            return true;
        });
    }
}
