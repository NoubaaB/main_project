<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Observers\ModelChangeObserver;

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
            'Production'  => \App\Models\Production::class,
            'User'  => \App\Models\User::class,
            'InventoryAdjustment' => \App\Models\InventoryAdjustment::class,
        ]);

        // Register model change observer for real-time broadcasting
        $observedModels = [
            \App\Models\Charge::class,
            \App\Models\Sell::class,
            \App\Models\Supplier::class,
            \App\Models\Client::class,
            \App\Models\Ship::class,
            \App\Models\Truck::class,
            \App\Models\Workforce::class,
            \App\Models\Section::class,
            \App\Models\Corporal::class,
            \App\Models\FortnightActivity::class,
            \App\Models\Warehouse::class,
            \App\Models\WarehouseCategory::class,
            \App\Models\Origin::class,
            \App\Models\Fishery::class,
            \App\Models\FishFamily::class,
            \App\Models\FishType::class,
            \App\Models\FishProcessType::class,
            \App\Models\PackageType::class,
            \App\Models\GuanoType::class,
            \App\Models\Formula::class,
            \App\Models\FixedCharge::class,
            \App\Models\SellCategory::class,
            \App\Models\ChargeCategory::class,
        ];

        foreach ($observedModels as $model) {
            $model::observe(ModelChangeObserver::class);
        }
    }
}
