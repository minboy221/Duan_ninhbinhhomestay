<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Interfaces\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class,
        );

        $this->app->bind(
            \App\Repositories\Contracts\ReportRepositoryInterface::class,
            \App\Repositories\Eloquent\ReportRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\BoardingHouseRepositoryInterface::class,
            \App\Repositories\BoardingHouseRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\RoomPostRepositoryInterface::class,
            \App\Repositories\RoomPostRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\ReviewRepositoryInterface::class,
            \App\Repositories\ReviewRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\TenantRepositoryInterface::class,
            \App\Repositories\TenantRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (is_dir(base_path('../public_html'))) {
            $this->app->usePublicPath(base_path('../public_html'));
        }
    }
}
