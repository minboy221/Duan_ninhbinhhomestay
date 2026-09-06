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

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'Contract' => \App\Models\Contract::class,
            'Invoice' => \App\Models\Invoice::class,
            'Room' => \App\Models\Room::class,
            'Post' => \App\Models\Post::class,
            'Property' => \App\Models\Property::class,
            'BoardingHouse' => \App\Models\BoardingHouse::class,
            'User' => \App\Models\User::class,
            'App\Models\Contract' => \App\Models\Contract::class,
            'App\Models\Invoice' => \App\Models\Invoice::class,
            'App\Models\Room' => \App\Models\Room::class,
            'App\Models\Post' => \App\Models\Post::class,
            'App\Models\Property' => \App\Models\Property::class,
            'App\Models\BoardingHouse' => \App\Models\BoardingHouse::class,
            'App\Models\User' => \App\Models\User::class,
        ]);
    }
}
