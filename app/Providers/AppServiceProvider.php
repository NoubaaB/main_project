<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        Relation::enforceMorphMap([
            'GuanoType' => \App\Models\GuanoType::class,
            'FishType'  => \App\Models\FishType::class,
            'PackageType'  => \App\Models\PackageType::class,
            'Charge'  => \App\Models\Charge::class,
            'Sell'  => \App\Models\Sell::class,
            'User'  => \App\Models\User::class,
        ]);
    }
}
