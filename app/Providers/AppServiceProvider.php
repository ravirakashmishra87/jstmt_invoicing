<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFive();

        View::composer('*', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            auth()->user()->loadMissing('role.permissions');

            $view->with('company', once(fn () => CompanyProfile::with('state')->first()));
        });
    }
}
