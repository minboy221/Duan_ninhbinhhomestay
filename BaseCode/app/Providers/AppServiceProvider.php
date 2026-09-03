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
            \App\Repositories\Eloquent\UserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ReportRepositoryInterface::class,
            \App\Repositories\Eloquent\ReportRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\BoardingHouseRepositoryInterface::class,
            \App\Repositories\Eloquent\BoardingHouseRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\RoomPostRepositoryInterface::class,
            \App\Repositories\Eloquent\RoomPostRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ReviewRepositoryInterface::class,
            \App\Repositories\Eloquent\ReviewRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\TenantRepositoryInterface::class,
            \App\Repositories\Eloquent\TenantRepository::class
        );
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (is_dir(base_path('../public_html')) && file_exists(base_path('../public_html/build/manifest.json'))) {
            $this->app->usePublicPath(base_path('../public_html'));
        }
    }
}
