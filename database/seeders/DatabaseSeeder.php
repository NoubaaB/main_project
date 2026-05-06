<?php

namespace Database\Seeders;

use App\Models\ChargeCategory;
use App\Models\Client;
use App\Models\DetailChargeCategory;
use App\Models\DetailShippingCategory;
use App\Models\DetailProductionCategory;
use App\Models\Fishery;
use App\Models\FishFamily;
use App\Models\FishProcessType;
use App\Models\FishType;
use App\Models\Formula;
use App\Models\GuanoType;
use App\Models\Origin;
use App\Models\PackageType;
use App\Models\ProductionCategory;
use App\Models\Role;
use App\Models\Ship;
use App\Models\ShippingCategory;
use App\Models\Supplier;
use App\Models\Truck;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Support\CsvReader;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::factory()->create([
            "name" => "admin",
            "is_super_admin" => true,
            "permissions" => Role::allPermissionKeys(),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            "role_id" => 1
        ]);

        #region formula
        Formula::create([
            "name_fr" => "Rendement Guano ",
            "name_en" => "Rendement Guano ",
            "name_es" => "Rendement Guano ",
            "description" => "Rendement for Guano",
            "enabled" => true,
            "type" => "charge",
            "created_by" => 1,
            "expressions" => [],
        ]);
        #endregion formula

        $guano_type_entier = GuanoType::create([
            "name_fr" => "Guano Entier",
            "name_en" => "Guano Entier",
            "name_es" => "Guano Entier",
            "name_in_invoice" => "Guano Entier",
            "scientific_name" => "Guano Entier",
            "description" => "Guano Entier",
        ]);

        $guano_type_hgt = GuanoType::create([
            "name_fr" => "Guano HGT",
            "name_en" => "Guano HGT",
            "name_es" => "Guano HGT",
            "name_in_invoice" => "Guano HGT",
            "scientific_name" => "Guano HGT",
            "description" => "Guano HGT",
        ]);

        $guano_type_hg = GuanoType::create([
            "name_fr" => "Guano HG",
            "name_en" => "Guano HG",
            "name_es" => "Guano HG",
            "name_in_invoice" => "Guano HG",
            "scientific_name" => "Guano HG",
            "description" => "Guano HG",
        ]);

        $guano_type_filet = GuanoType::create([
            "name_fr" => "Guano Filet",
            "name_en" => "Guano Filet",
            "name_es" => "Guano Filet",
            "name_in_invoice" => "Guano Filet",
            "scientific_name" => "Guano Filet",
            "description" => "Guano Filet",
        ]);

        #region init

        $path = 'storage/csv/providers.csv';

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (count(json_decode($data["type"])) == 2) {
                Supplier::create([
                    "name" => $data["name_provider"],
                    "id" => $data["id"]
                ]);
                Client::create([
                    "name" => $data["name_provider"],
                    "id" => $data["id"]
                ]);
            } else {
                if (in_array("Fournisseur", json_decode($data["type"]))) {
                    Supplier::create([
                        "name" => $data["name_provider"],
                        "id" => $data["id"]
                    ]);
                } else {
                    Client::create([
                        "name" => $data["name_provider"],
                        "id" => $data["id"]
                    ]);
                }
            }
        }

        $path = ('storage/csv/origins.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            Origin::create([
                "name" => $data["name_origin"],
            ]);
        }
        $path = ('storage/csv/ships.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if ($data["ship"] == 1) {
                Ship::create([
                    "name" => $data["ship_name"]
                ]);
            } else {
                Truck::create([
                    "registration_plate" => $data["ship_name"]
                ]);
            }
        }

        $path = ('storage/csv/types.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {

            if (FishType::where("name_fr", $data["name_type"])->count() == 0) {
                $fish_process_type = FishProcessType::Where("name", $data["process"])->first();
                $fish_process_type_id = null;
                if ($fish_process_type) {
                    $fish_process_type_id = $fish_process_type->id;
                } else {
                    $fish_process_type = FishProcessType::create([
                        "name" => $data["process"],
                        "description" => null
                    ]);
                    $fish_process_type_id = $fish_process_type->id;
                }
                FishType::create([
                    "id" => $data["id"],
                    "name_fr" => $data["name_type"],
                    "name_en" => $data["name_type"],
                    "name_es" => $data["name_type"],
                    "type" => $data["code_article"] == "congele" ? "frozen" : "fresh",
                    "name_in_invoice" => $data["name_type"],
                    "fish_family_id" => $data["family_id"] == "NULL" ? NULL : $data["family_id"],
                    "fish_process_type_id" => $fish_process_type_id
                ]);
            }
        }
        $path = ('storage/csv/places.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            Fishery::create([
                "name" => $data["name_place"],
                "name_fr" => $data["name_place"],
                "name_en" => $data["name_place"],
                "name_es" => $data["name_place"],
            ]);
        }
        $path = ('storage/csv/families.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (FishFamily::where("name_en", $data["name"])->count() == 0) {
                FishFamily::create([
                    "id" => $data["id"],
                    "name_en" => $data["name"],
                    "name_es" => $data["name"],
                    "name_fr" => $data["name"]
                ]);
            }
        }

        $path = ('storage/csv/containers.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (PackageType::where("name_fr", $data["name_container"])->count() == 0) {
                PackageType::create([
                    "id" => $data["id"],
                    "name_fr" => $data["name_container"],
                    "name_en" => $data["name_container"],
                    "name_es" => $data["name_container"],
                    "size" => $data["size"]
                ]);
            }
        }

        #endregion init

        #region warehouse categories
        $whCatFresh = WarehouseCategory::create(['name' => 'Entrepôt Frais']);
        $whCatFrozen = WarehouseCategory::create(['name' => 'Entrepôt Congelé']);
        $whCatGuano = WarehouseCategory::create(['name' => 'Entrepôt Guano']);
        $whCatPackage = WarehouseCategory::create(['name' => 'Entrepôt Emballage']);
        #endregion warehouse categories

        #region warehouses
        Warehouse::create([
            'name' => 'Entrepôt Frais Principal',
            'description' => 'Main fresh fish warehouse',
            'warehouse_category_id' => $whCatFresh->id,
            'warehouse_type' => 'FishType',
            'has_sejour' => true,
            'operation_service_fee' => 0,
        ]);
        Warehouse::create([
            'name' => 'Entrepôt Congelé Principal',
            'description' => 'Main frozen fish warehouse',
            'warehouse_category_id' => $whCatFrozen->id,
            'warehouse_type' => 'FishType',
            'has_sejour' => true,
            'operation_service_fee' => 0,
        ]);
        Warehouse::create([
            'name' => 'Entrepôt Guano',
            'description' => 'Guano warehouse',
            'warehouse_category_id' => $whCatGuano->id,
            'warehouse_type' => 'GuanoType',
            'has_sejour' => false,
            'operation_service_fee' => 0,
        ]);
        Warehouse::create([
            'name' => 'Entrepôt Emballage',
            'description' => 'Package warehouse',
            'warehouse_category_id' => $whCatPackage->id,
            'warehouse_type' => 'PackageType',
            'has_sejour' => false,
            'operation_service_fee' => 0,
        ]);
        #endregion warehouses

        #region charge categories
        $chargeCat1 = ChargeCategory::create([
            'name' => 'Du frais à la production',
            'name_fr' => 'Du frais à la production',
            'name_en' => 'Fresh To Production',
            'name_es' => 'Du frais à la production',
            'description' => 'Fresh Fish that gonna enter the production section to become Frozen fish later and be stocked to be sold',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => true,
            'enable_fishery_id' => true,
            'enable_ship_id' => true,
            'enable_origin_id' => true,
            'enable_truck_id' => true,
            'enable_suppliers' => true,
            'is_production' => true,
            'enable_invoices' => false,
        ]);

        $chargeCat2 = ChargeCategory::create([
            'name' => 'Frais Revendue',
            'name_fr' => 'Frais Revendue',
            'name_en' => 'Fresh To Sell',
            'name_es' => 'Frais Revendue',
            'description' => 'Fresh fish that stocked to be sold later',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => true,
            'enable_fishery_id' => true,
            'enable_ship_id' => true,
            'enable_origin_id' => true,
            'enable_truck_id' => true,
            'enable_suppliers' => true,
            'is_production' => false,
            'enable_invoices' => false,
        ]);

        $chargeCat3 = ChargeCategory::create([
            'name' => 'Congele',
            'name_fr' => 'Congele',
            'name_en' => 'Frozen',
            'name_es' => 'Congele',
            'description' => 'Frozen Fish that stocked to be sold later',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_fishery_id' => true,
            'enable_ship_id' => true,
            'enable_origin_id' => true,
            'enable_truck_id' => true,
            'enable_suppliers' => true,
            'is_production' => false,
            'enable_invoices' => false,
        ]);

        $chargeCat4 = ChargeCategory::create([
            'name' => 'Guano Revendue',
            'name_fr' => 'GuanoRevendue',
            'name_en' => 'Guano To Sell',
            'name_es' => 'Guano Revendue',
            'description' => 'Guano that stocked to be sold later',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_fishery_id' => false,
            'enable_ship_id' => false,
            'enable_origin_id' => false,
            'enable_truck_id' => true,
            'enable_suppliers' => true,
            'is_production' => false,
            'enable_invoices' => false,
        ]);

        $chargeCat5 = ChargeCategory::create([
            'name' => 'Carton',
            'name_fr' => 'Carton',
            'name_en' => 'Carton',
            'name_es' => 'Carton',
            'description' => 'Carton That will be stocked to be Consuming in the Production Process',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_fishery_id' => false,
            'enable_ship_id' => false,
            'enable_origin_id' => false,
            'enable_truck_id' => true,
            'enable_suppliers' => true,
            'is_production' => false,
            'enable_invoices' => false,
        ]);
        #endregion charge categories

        #region detail charge categories
        DetailChargeCategory::create([
            'name_fr' => 'Frais Poisson',
            'name_en' => 'Fresh Fish',
            'name_es' => 'Frais Poisson',
            'description' => 'Fresh Fish For the Charge that goes in reception (first phase)',
            'article_type' => 'FishType',
            'fish_type_filter' => 'fresh',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => false,
            'allow_nbr_box' => false,
            'allow_warehouse' => false,
            'warehouse_operation' => null,
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'charge_category_id' => $chargeCat1->id,
        ]);

        DetailChargeCategory::create([
            'name_fr' => 'Frais Poisson',
            'name_en' => 'Fresh Fish',
            'name_es' => 'Frais Poisson',
            'description' => 'Fresh Fish that gonna stocked to be sold later',
            'article_type' => 'FishType',
            'fish_type_filter' => 'fresh',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => null,
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'charge_category_id' => $chargeCat2->id,
        ]);

        DetailChargeCategory::create([
            'name_fr' => 'Congele Poisson',
            'name_en' => 'Frozen Fish',
            'name_es' => 'Congele Poisson',
            'description' => 'Frozen Fish that gonna stocked and be sold later',
            'article_type' => 'FishType',
            'fish_type_filter' => 'frozen',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => null,
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'charge_category_id' => $chargeCat3->id,
        ]);

        DetailChargeCategory::create([
            'name_fr' => 'Guano',
            'name_en' => 'Guano',
            'name_es' => 'Guano',
            'description' => 'Guano that be stocked and be sold later',
            'article_type' => 'GuanoType',
            'fish_type_filter' => 'all',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => false,
            'allow_nbr_box' => false,
            'allow_warehouse' => true,
            'warehouse_operation' => null,
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'charge_category_id' => $chargeCat4->id,
        ]);

        DetailChargeCategory::create([
            'name_fr' => 'Emballage',
            'name_en' => 'Emballage',
            'name_es' => 'Emballage',
            'description' => 'Emballage that will stocked in the stock to be consuming in the production process (second phase)',
            'article_type' => 'PackageType',
            'fish_type_filter' => 'all',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => null,
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'charge_category_id' => $chargeCat5->id,
        ]);
        #endregion detail charge categories

        #region shipping categories
        $shipCat1 = ShippingCategory::create([
            'name' => 'Vente Locale',
            'name_fr' => 'Vente Locale',
            'name_en' => 'Local Sale',
            'name_es' => 'Venta Local',
            'description' => 'Local shipping of fish products',
            'enable_shipping_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_fishery_id' => false,
            'enable_ship_id' => false,
            'enable_origin_id' => false,
            'enable_truck_id' => true,
            'enable_clients' => true,
            'enable_direction' => true,
            'enable_invoices' => true,
        ]);

        $shipCat2 = ShippingCategory::create([
            'name' => 'Vente Export',
            'name_fr' => 'Vente Export',
            'name_en' => 'Export Sale',
            'name_es' => 'Venta Export',
            'description' => 'Export shipping of fish products',
            'enable_shipping_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_fishery_id' => false,
            'enable_ship_id' => true,
            'enable_origin_id' => true,
            'enable_truck_id' => true,
            'enable_clients' => true,
            'enable_direction' => true,
            'enable_invoices' => true,
        ]);
        #endregion shipping categories

        #region detail shipping categories
        DetailShippingCategory::create([
            'name_fr' => 'Poisson Congelé',
            'name_en' => 'Frozen Fish',
            'name_es' => 'Pescado Congelado',
            'description' => 'Frozen fish shipped from warehouse',
            'article_type' => 'FishType',
            'fish_type_filter' => 'frozen',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_out',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'shipping_category_id' => $shipCat1->id,
        ]);

        DetailShippingCategory::create([
            'name_fr' => 'Poisson Frais',
            'name_en' => 'Fresh Fish',
            'name_es' => 'Pescado Fresco',
            'description' => 'Fresh fish shipped from warehouse',
            'article_type' => 'FishType',
            'fish_type_filter' => 'fresh',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_out',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'shipping_category_id' => $shipCat1->id,
        ]);

        DetailShippingCategory::create([
            'name_fr' => 'Guano',
            'name_en' => 'Guano',
            'name_es' => 'Guano',
            'description' => 'Guano products shipped from warehouse',
            'article_type' => 'GuanoType',
            'fish_type_filter' => 'all',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => false,
            'allow_nbr_box' => false,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_out',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'shipping_category_id' => $shipCat1->id,
        ]);

        DetailShippingCategory::create([
            'name_fr' => 'Poisson Congelé Export',
            'name_en' => 'Frozen Fish Export',
            'name_es' => 'Pescado Congelado Export',
            'description' => 'Frozen fish for export shipment',
            'article_type' => 'FishType',
            'fish_type_filter' => 'frozen',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_out',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => true,
            'shipping_category_id' => $shipCat2->id,
        ]);
        #endregion detail shipping categories

        #region production categories
        $prodCat1 = ProductionCategory::create([
            'name' => 'Production Congélation',
            'name_fr' => 'Production Congélation',
            'name_en' => 'Freezing Production',
            'name_es' => 'Producción Congelación',
            'description' => 'Production process that transforms fresh fish into frozen fish products',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => true,
            'enable_production_tracking_number' => true,
            'enable_production_type' => true,
            'enable_fishery_id' => true,
            'enable_ship_id' => false,
            'enable_origin_id' => true,
            'enable_truck_id' => false,
            'enable_suppliers' => true,
            'enable_clients' => false,
            'enable_invoices' => false,
        ]);

        $prodCat2 = ProductionCategory::create([
            'name' => 'Production Guano',
            'name_fr' => 'Production Guano',
            'name_en' => 'Guano Production',
            'name_es' => 'Producción Guano',
            'description' => 'Guano byproduct production from fish processing',
            'enable_reception_tracking_number' => true,
            'enable_tracking_onp_numbers' => false,
            'enable_production_tracking_number' => true,
            'enable_production_type' => true,
            'enable_fishery_id' => false,
            'enable_ship_id' => false,
            'enable_origin_id' => false,
            'enable_truck_id' => false,
            'enable_suppliers' => false,
            'enable_clients' => false,
            'enable_invoices' => false,
        ]);
        #endregion production categories

        #region detail production categories
        DetailProductionCategory::create([
            'name_fr' => 'Poisson Congelé Produit',
            'name_en' => 'Frozen Fish Output',
            'name_es' => 'Producción Pescado Congelado',
            'description' => 'Frozen fish output from the production line (stock_in)',
            'article_type' => 'FishType',
            'fish_type_filter' => 'frozen',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_in',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => false,
            'production_category_id' => $prodCat1->id,
        ]);

        DetailProductionCategory::create([
            'name_fr' => 'Guano Produit',
            'name_en' => 'Guano Output',
            'name_es' => 'Producción Guano',
            'description' => 'Guano byproduct output from production (stock_in)',
            'article_type' => 'GuanoType',
            'fish_type_filter' => 'all',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => false,
            'allow_nbr_box' => false,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_in',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => false,
            'production_category_id' => $prodCat2->id,
        ]);

        DetailProductionCategory::create([
            'name_fr' => 'Emballage Consommé',
            'name_en' => 'Package Consumed',
            'name_es' => 'Embalaje Consumido',
            'description' => 'Packaging consumed during production (stock_out)',
            'article_type' => 'PackageType',
            'fish_type_filter' => 'all',
            'allow_weight' => true,
            'allow_unit_price' => true,
            'allow_price' => true,
            'allow_package_type' => true,
            'allow_nbr_box' => true,
            'allow_warehouse' => true,
            'warehouse_operation' => 'stock_out',
            'allow_detail_truck_id' => false,
            'include_in_invoice' => false,
            'production_category_id' => $prodCat1->id,
        ]);
        #endregion detail production categories
    }
}
