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
                    "name" => $data["name_provider"]
                ]);
                Client::create([
                    "name" => $data["name_provider"]
                ]);
            } else {
                if (in_array("Fournisseur", json_decode($data["type"]))) {
                    Supplier::create([
                        "name" => $data["name_provider"]
                    ]);
                } else {
                    Client::create([
                        "name" => $data["name_provider"]
                    ]);
                }
            }
        }

        $path = 'storage/csv/chambres.csv';
        $typeWarehouse = TypeWarehouse::create([
            "name"=>"congele"
        ]);
        $rows = CsvReader::read($path);

        foreach ($rows as $data) {
            if (Warehouse::where("name", $data['name_chambre'])->count() == 0) {
                Warehouse::create([
                    "name" => $data['name_chambre']
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
                    "type_warehouse_id" => $typeWarehouse->id,
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

        $path =('storage/csv/types.csv');

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

        $type_charge =  TypeCharge::factory()->create([
            "description" => "Cotiere",
            "name_en" => "Cotiere",
            "name_fr" => "Cotiere",
            "name_es" => "Cotiere",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        #endregion
        $path = ('storage/csv/frais.csv');

        $rows = CsvReader::read($path);
        foreach ($rows as $data) {
            // dd(json_decode($data["types_reception"]));
            $charge = Charge::create([
                "date" => $data["date_reception"],
                "tracking_numbers" => $data["num_bon_receptions"],
                "origin_id" => $data["origin_id"],
                "fishery_id" => $data["place_id"],
                "type_charge_id" => $type_charge->id,
                "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
            ]);

            $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
            $charge->suppliers()->attach($suppliers);
            foreach (json_decode($data["types_reception"]) as $item) {
                $charge->non_warehouse_charge_items()->create([
                    "fish_type_id" => $item->type_id,
                    "package_id" => null,

                    "unit_price" => $item->price,
                    "total_weight" => $item->weight,
                    "total_price" => ($item->price * $item->weight),
                    "total_package_count" => $item->type_id,
                ]);
            }
        }

    }
}
