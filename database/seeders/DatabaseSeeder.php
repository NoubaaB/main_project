<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Sell;
use App\Models\Ship;
use App\Models\User;
use App\Models\Truck;
use App\Models\Charge;
use App\Models\Client;
use App\Models\Origin;
use App\Models\Fishery;
use App\Models\Package;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\FishType;
use App\Models\Supplier;
use App\Models\TypeSell;
use App\Models\Warehouse;
use App\Models\TypeCharge;
use App\Support\CsvReader;
use App\Models\FishFamilly;
use App\Models\TypeWarehouse;
use App\Models\WarehouseFish;
use Illuminate\Database\Seeder;
use App\Models\WarehouseFishTrackingNumberOperation;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        Role::factory()->create([
            "name" => "admin"
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            "role_id" => 1
        ]);

        #region init
        $path = 'storage/csv/providers.csv';

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (count(json_decode($data["type"])) == 2) {
                Supplier::create([
                    "name" => $data["name_provider"],
                    "id" => $data["id"]                ]);
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

        $path = 'storage/csv/chambres.csv';
        $typeWarehouse_local = TypeWarehouse::create([
            "name" => "local",
            "description"=>"its meaen that is local warehouse of our production"
        ]);
        $typeWarehouse_foreign = TypeWarehouse::create([
            "name" => "foreign",
            "description" => "its meaen that is foreign warehouse of our production in other company or unite fish production"
        ]);
        $typeWarehouse_client = TypeWarehouse::create([
            "name" => "client",
            "description" => "its meaen that is local warehouse thta hold his fishes and goods"
        ]);
        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            $count = Warehouse::where("name", $data['name_chambre'])->count();
            $type_warehouse_id = $typeWarehouse_client->id;
            if("CHAMBRE AKF 2" == $data['name_chambre']){
                $type_warehouse_id = $typeWarehouse_local->id;
            }
            if ($count == 0) {
                Warehouse::create([
                    "id" => $data['id'],
                    "name" => $data['name_chambre'],
                    "type_warehouse_id" => $type_warehouse_id
                ]);
            }else{
                Warehouse::create([
                    "id" => $data['id'],
                    "name" => $data['name_chambre']."_".rand(),
                    "type_warehouse_id" => $type_warehouse_id
                ]);
            }
        }

        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            foreach (FishType::all() as $fish_type) {
                $warehouse->warehouse_fishes()->create([
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_id" => $warehouse->id,
                    "fish_type_id" => $fish_type->id
                ]);
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

            if (FishType::where("name", $data["name_type"])->count() == 0) {
                FishType::create([
                    "id" => $data["id"],
                    "name" => $data["name_type"],
                    "fish_familly_id" => $data["family_id"] == "NULL" ? NULL : $data["family_id"]
                ]);
            }
        }
        $path = ('storage/csv/places.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            Fishery::create([
                "name" => $data["name_place"],
            ]);
        }
        $path = ('storage/csv/families.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (FishFamilly::where("name", $data["name"])->count() == 0) {
                FishFamilly::create([
                    "id" => $data["id"],
                    "name" => $data["name"]
                ]);
            }
        }

        $path = ('storage/csv/containers.csv');

        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (Package::where("name", $data["name_container"])->count() == 0) {
                Package::create([
                    "id" => $data["id"],
                    "name" => $data["name_container"],
                    "size" => $data["size"]
                ]);
            }
        }

        $type_charge_frais =  TypeCharge::factory()->create([
            "description" => "Frais",
            "name_en" => "Frais",
            "name_fr" => "Frais",
            "name_es" => "Frais",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        #endregion
        $path = ('storage/csv/frais.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            $charge = Charge::create([
                "date" => $data["date_reception"],
                "tracking_numbers" => $data["num_bon_receptions"],
                "origin_id" => $data["origin_id"],
                "fishery_id" => $data["place_id"],
                "type_charge_id" => $type_charge_frais->id,
                "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
            ]);

            $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
            $charge->suppliers()->attach($suppliers);
            foreach (json_decode($data["types_reception"]) as $item) {
                $charge->non_warehouse_charge_operations()->create([
                    "fish_type_id" => $item->type_id,
                    "package_id" => null,

                    "unit_price" => $item->price,
                    "total_weight" => $item->weight,
                    "total_price" => ($item->price * $item->weight),
                    "total_package_count" => $item->type_id,
                ]);
            }
            $charge->calc();
        }

        $path = ('storage/csv/congeles.csv');

        $rows = CsvReader::read($path);

        $type_charge_congele =  TypeCharge::factory()->create([
            "description" => "Congele",
            "name_en" => "Congele",
            "name_fr" => "Congele",
            "name_es" => "Congele",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        foreach ($rows as $data) {
            $charge = Charge::create([
                "date" => $data["date_reception"],
                "tracking_numbers" => $data["num_bon_receptions"],
                "origin_id" => $data["origin_id"],
                "fishery_id" => $data["place_id"],
                "type_charge_id" => $type_charge_congele->id,
                "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
            ]);

            $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
            $charge->suppliers()->attach($suppliers);
            foreach (json_decode($data["types_production"]) as $item) {
                $warehouse = Warehouse::find($item->chambre_id);
                $warehouse_fish = null;
                if ($warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->count() != 1) {
                    $warehouse_fish = $warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->first();
                }
                $warehouse_fish = $warehouse->warehouse_fishes()->create([
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_id" => $warehouse->id,
                    "fish_type_id" => $item->type_id
                ]);
                $warehouse_fish_tracking_number = null;
                if ($warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                    $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                }
                $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->create([
                    "tracking_numbers" => [(string)$item->num_bon_reception],
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_fish_id" => $warehouse_fish->id,
                    "fish_type_id" => $item->type_id
                ]);

                $s_f_t_n_o = $warehouse_fish_tracking_number->warehouse_fish_tracking_number_operations()->create([
                    "type_operation" => "in",
                    "date" => $charge->date,
                    "unit_price" => $item->pu,
                    "total_weight" => $item->total_weight_container,
                    "total_price" => $item->total_weight_container * $item->pu,
                    "total_package_count" => round($item->total_weight_container / Package::find($item->container_id)->size, 2),
                    "fish_type_id" => $item->type_id,
                    "package_id" => $item->container_id,
                ]);
                // $warehouse->calc();
                $charge->warehouse_fish_tracking_number_operations()->save($s_f_t_n_o);
            }
            $charge->calc();
        }

        $type_sell =  TypeSell::factory()->create([
            "description" => "Vente Service Congelation",
            "name_en" => "Vente Service Congelation",
            "name_fr" => "Vente Service Congelation",
            "name_es" => "Vente Service Congelation",
            "list_type_details" => 1,
        ]);

        $path = ('storage/csv/congelation_clients.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            $sell = Sell::create([
                "date" => $data["date_reception"],
                "tracking_numbers" => $data["num_bon_receptions"],
                "origin_id" => $data["origin_id"],
                "fishery_id" => $data["place_id"],
                "type_sell_id" => $type_sell->id,
                "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
            ]);

            $clients = Client::find([$data["provider_id"]]);
            $sell->clients()->attach($clients);
            foreach (json_decode($data["types_production"]) as $item) {
                $warehouse = Warehouse::find($item->chambre_id);
                $warehouse_fish = null;
                if ($warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->count() != 1) {
                    $warehouse_fish = $warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->first();
                }
                $warehouse_fish = $warehouse->warehouse_fishes()->create([
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_id" => $warehouse->id,
                    "fish_type_id" => $item->type_id
                ]);
                $warehouse_fish_tracking_number = null;
                if ($warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                    $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                }
                $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->create([
                    "tracking_numbers" => [(string)$item->num_bon_reception],
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_fish_id" => $warehouse_fish->id,
                    "fish_type_id" => $item->type_id
                ]);

                $s_f_t_n_o = $warehouse_fish_tracking_number->warehouse_fish_tracking_number_operations()->create([
                    "type_operation" => "in",
                    "date" => $sell->date,
                    "unit_price" => $item->pu,
                    "total_weight" => $item->total_weight_container,
                    "total_price" => $item->total_weight_container * $item->pu,
                    "total_package_count" => round($item->total_weight_container / Package::find($item->container_id)->size, 2),
                    "fish_type_id" => $item->type_id,
                    "package_id" => $item->container_id,
                ]);
                $sell->warehouse_fish_tracking_number_operations()->save($s_f_t_n_o);
            }
            $sell->calc();
        }


        $path = ('storage/csv/stock_exits.csv');

        $type_sell_sortie_client =  TypeSell::factory()->create([
            "description" => "Sortie Stock Client",
            "name_en" => "Sortie Stock Client",
            "name_fr" => "Sortie Stock Client",
            "name_es" => "Sortie Stock Client",
            "list_type_details" => 1,
        ]);

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            echo $data['id']."\n";
            $sell = Sell::create([
                "date" => $data["date_sortie"],
                "tracking_numbers" => $data["num_bon_sorties"],
                "origin_id" => $data["origin_id"]??1,
                "fishery_id" => $data["place_id"]??1,
                "type_sell_id" => $type_sell_sortie_client->id,
                "ship_id" => null,
            ]);

            $clients = Client::find([$data["provider_id"]]);
            $sell->clients()->attach($clients);
            foreach (json_decode($data["types_sortie"]) as $item) {
                $warehouse = Warehouse::find($item->chambre_id);
                $warehouse_fish = null;
                if ($warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->count() != 1) {
                    $warehouse_fish = $warehouse->warehouse_fishes()->where("fish_type_id", $item->type_id)->first();
                }
                $warehouse_fish = $warehouse->warehouse_fishes()->create([
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_id" => $warehouse->id,
                    "fish_type_id" => $item->type_id
                ]);
                $warehouse_fish_tracking_number = null;
                if ($warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                    $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                }
                $warehouse_fish_tracking_number = $warehouse_fish->warehouse_fish_tracking_numbers()->create([
                    "tracking_numbers" => [(string)$item->num_bon_reception],
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "total_package_count" => 0.00,
                    "warehouse_fish_id" => $warehouse_fish->id,
                    "fish_type_id" => $item->type_id
                ]);

                $s_f_t_n_o = $warehouse_fish_tracking_number->warehouse_fish_tracking_number_operations()->create([
                    "type_operation" => "out",
                    "date" => $sell->date,
                    "unit_price" => $item->pu,
                    "total_weight" => $item->weight,
                    "total_price" => $item->weight * $item->pu,
                    "total_package_count" => round($item->weight / Package::find($item->container_id)->size, 2),
                    "fish_type_id" => $item->type_id,
                    "package_id" => $item->container_id,
                ]);
                $sell->warehouse_fish_tracking_number_operations()->save($s_f_t_n_o);
            }
            $sell->calc();
        }




        Warehouse::each(fn($q)=>$q->calc());

    }
}
