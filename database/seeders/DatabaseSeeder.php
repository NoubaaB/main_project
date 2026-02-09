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
use App\Models\FishType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Supplier;
use App\Models\GuanoType;
use App\Models\Warehouse;
use App\Support\CsvReader;
use App\Models\FishFamily;
use App\Models\FixedCharge;
use App\Models\PackageType;
use App\Models\SellCategory;
use App\Models\ChargeCategory;
use App\Models\FishProcessType;
use App\Models\Formula;
use Illuminate\Database\Seeder;
use App\Models\WarehouseCategory;
use App\Models\WarehouseCategoryOperation;

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

        #region formula
        Formula::create([
            "name_fr"=>"Rendement Guano ",
            "name_en"=>"Rendement Guano ",
            "name_es"=>"Rendement Guano ",
            "description"=>"Rendement for Guano",
            "enabled"=>true,
            "type"=>"charge",
            "created_by"=>1,
            "expressions"=> [
                [
                        "expression" => "cc1_weight",
                        "chart_config" => [
                            "chart_color" => "#0743acb6",
                            "chart_type" => "area",
                            "opposite" => true,
                            "name_fr" => "HGT Qte",
                            "name_en" => "HGT Qte",
                            "name_es" => "HGT Qte",
                            "scale"=>"KG",
                            "mdi_icon"=> "mdi-weight-kilogram",
                            "display_yaxis"=>true,
                            "use_avg"=>false
                        ],
                        "variables" => [
                            [
                                "alias" => "cc1_weight",
                                "kind" => "charge",
                                "category_id" => 1,
                                "stockable_type" => null,
                                "stockable_id" => null,
                                "field" => "total_weight"
                            ]
                    ]
                ],
                [
                        "expression" => "(cc1_weight / cc2_weight)*100",
                        "chart_config" => [
                            "chart_color" => "#330099ff",
                            "chart_type" => "column",
                            "opposite" => false,
                            "name_fr" => "Entier",
                            "name_en" => "Entier",
                            "name_es" => "Entier",
                            "scale"=>"%",
                            "mdi_icon"=>"mdi-percent",
                            "display_yaxis"=>true,
                            "use_avg"=>true
                        ],
                        "variables" => [
                            [
                                "alias" => "cc1_weight",
                                "kind" => "charge",
                                "category_id" => 3,
                                "stockable_type" => class_basename(GuanoType::class),
                                "stockable_id" => 1,
                                "field" => "total_weight"
                            ],
                            [
                                "alias" => "cc2_weight",
                                "kind" => "charge",
                                "category_id" => 1,
                                "stockable_type" => null,
                                "stockable_id" => null,
                                "field" => "total_weight"
                            ]
                    ]
                ],
                [
                        "expression" => "(cc1_weight / cc2_weight)*100",
                        "chart_config" => [
                            "chart_color" => "#1eaeecff",
                            "chart_type" => "column",
                            "opposite" => false,
                            "name_fr" => "HGT",
                            "name_en" => "HGT",
                            "name_es" => "HGT",
                            "scale"=>"%",
                            "mdi_icon"=>"mdi-percent",
                            "display_yaxis"=>false,
                            "use_avg"=>true
                        ],
                        "variables" => [
                            [
                                "alias" => "cc1_weight",
                                "kind" => "charge",
                                "category_id" => 3,
                                "stockable_type" => class_basename(GuanoType::class),
                                "stockable_id" => 2,
                                "field" => "total_weight"
                            ],
                            [
                                "alias" => "cc2_weight",
                                "kind" => "charge",
                                "category_id" => 1,
                                "stockable_type" => null,
                                "stockable_id" => null,
                                "field" => "total_weight"
                            ]
                    ]
                ],
                // [
                //         "expression" => "(cc1_weight / cc2_weight)*100",
                //         "chart_config" => [
                //             "chart_color" => "#c5f51aff",
                //             "chart_type" => "column",
                //             "opposite" => false,
                //             "name_fr" => "HG",
                //             "name_en" => "HG",
                //             "name_es" => "HG",
                //             "scale"=>"%",
                //             "mdi_icon"=>"mdi-percent",
                //             "display_yaxis"=>false,
                //             "use_avg"=>true
                //         ],
                //         "variables" => [
                //             [
                //                 "alias" => "cc1_weight",
                //                 "kind" => "charge",
                //                 "category_id" => 3,
                //                 "stockable_type" => class_basename(GuanoType::class),
                //                 "stockable_id" => 3,
                //                 "field" => "total_weight"
                //             ],
                //             [
                //                 "alias" => "cc2_weight",
                //                 "kind" => "charge",
                //                 "category_id" => 1,
                //                 "stockable_type" => null,
                //                 "stockable_id" => null,
                //                 "field" => "total_weight"
                //             ]
                //     ]
                // ],
                // [
                //         "expression" => "(cc1_weight / cc2_weight)*100",
                //         "chart_config" => [
                //             "chart_color" => "#17ea33ff",
                //             "chart_type" => "column",
                //             "opposite" => false,
                //             "name_fr" => "Filet",
                //             "name_en" => "Filet",
                //             "name_es" => "Filet",
                //             "scale"=>"%",
                //             "mdi_icon"=>"mdi-percent",
                //             "display_yaxis"=>false,
                //             "use_avg"=>true
                //         ],
                //         "variables" => [
                //             [
                //                 "alias" => "cc1_weight",
                //                 "kind" => "charge",
                //                 "category_id" => 3,
                //                 "stockable_type" => class_basename(GuanoType::class),
                //                 "stockable_id" => 4,
                //                 "field" => "total_weight"
                //             ],
                //             [
                //                 "alias" => "cc2_weight",
                //                 "kind" => "charge",
                //                 "category_id" => 1,
                //                 "stockable_type" => null,
                //                 "stockable_id" => null,
                //                 "field" => "total_weight"
                //             ]
                //     ]
                // ]
            ],
        ]);
        #endregion formula

        #region Fixed Charges and Type Charges Seeders
        FixedCharge::create([
            "name" => "Acompte 30%",
            "start_date" => "2025-10-01",
            "description" => "Acompte 30% sur total facture",
            "amount" => 30.00,
            "fixed_periodic_charge" => "daily",
            "charge_category_id" => 1
        ]);

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

        $guano_type_filet= GuanoType::create([
            "name_fr" => "Guano Filet",
            "name_en" => "Guano Filet",
            "name_es" => "Guano Filet",
            "name_in_invoice" => "Guano Filet",
            "scientific_name" => "Guano Filet",
            "description" => "Guano Filet",
        ]);

        $charge_category_frais =  ChargeCategory::factory()->create([
            "description" => "Frais",
            "name_en" => "Frais",
            "name_fr" => "Frais",
            "name_es" => "Frais",
            "enable_tracking_numbers" => true,
            "enable_fishery_id" => true,
            "enable_ship_id" => true,
            "enable_origin_id" => true,
            "enable_supplier_process_id" => true,
            "enable_tracking_onp_numbers" => true
        ]);


        $charge_category_congele =  ChargeCategory::factory()->create([
            "description" => "Congele",
            "name_en" => "Congele",
            "name_fr" => "Congele",
            "name_es" => "Congele",
            "enable_tracking_numbers" => true,
            "enable_fishery_id" => true,
            "enable_ship_id" => true,
            "enable_origin_id" => true,
            "enable_supplier_process_id" => true,
            "enable_tracking_onp_numbers" => true
        ]);

        $charge_category_guano_production =  ChargeCategory::factory()->create([
            "description" => "Guano Production",
            "name_en" => "Guano Production",
            "name_fr" => "Guano Production",
            "name_es" => "Guano Production",
            "enable_tracking_numbers" => true,
            "enable_fishery_id" => true,
            "enable_ship_id" => true,
            "enable_origin_id" => true,
            "enable_supplier_process_id" => true,
            "enable_tracking_onp_numbers" => true
        ]);

        #region Charge Detail Categories Seeders

        $warehouse_category_operation_frais_mp =  WarehouseCategoryOperation::create([
            "name_fr" => "Frais MP",
            "name_en" => "Frais MP",
            "name_es" => "Frais MP",
            "description" => "Frais Matiere Premiere",
            "warehouse_type" => "FishType",
            "allow_weight"=>true,
            "allow_price"=>true,
            "allow_package_type"=>false,
            "allow_warehouse"=>false
        ]);

        $warehouse_category_operation_frais_arvd =  WarehouseCategoryOperation::create([
            "name_fr" => "Frais Achat Revendu",
            "name_en" => "Frais Achat Revendu",
            "name_es" => "Frais Achat Revendu",
            "description" => "Frais Achat Revendu",
            "warehouse_type" => "FishType",
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => true,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_congele_p =  WarehouseCategoryOperation::create([
            "name_fr" => "Congele P",
            "name_en" => "Congele P",
            "name_es" => "Congele P",
            "description" => "Congele Production",
            "warehouse_type" => "FishType",
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => true,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_congele_achete =  WarehouseCategoryOperation::create([
            "name_fr" => "Congele Achete",
            "name_en" => "Congele Achete",
            "name_es" => "Congele Achete",
            "description" => "Congele Achete",
            "warehouse_type" => "FishType",
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => true,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_guano_production =  WarehouseCategoryOperation::create([
            "name_fr" => "Guano Production",
            "name_en" => "Guano Production",
            "name_es" => "Guano Production",
            "description" => "Guano Production",
            "warehouse_type" => "GuanoType",
            "allow_weight" => true,
            "allow_price" => false,
            "allow_package_type" => false,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_guano_arvd =  WarehouseCategoryOperation::create([
            "name_fr" => "Guano Achat Revendu",
            "name_en" => "Guano Achat Revendu",
            "name_es" => "Guano Achat Revendu",
            "description" => "Guano Achat Revendu",
            "warehouse_type" => "GuanoType",
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => false,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_glace =  WarehouseCategoryOperation::create([
            "name_fr" => "Glace",
            "name_en" => "Glace",
            "name_es" => "Glace",
            "description" => "Glace",
            "warehouse_type" => null,
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => false,
            "allow_warehouse" => false
        ]);

        $warehouse_category_operation_transport_on_charges =  WarehouseCategoryOperation::create([
            "name_fr" => "Transport on Charges",
            "name_en" => "Transport on Charges",
            "name_es" => "Transport on Charges",
            "description" => "Transport on Charges",
            "warehouse_type" => null,
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => false,
            "allow_warehouse" => false
        ]);

        $warehouse_category_operation_transport_on_sells =  WarehouseCategoryOperation::create([
            "name_fr" => "Transport on Sells",
            "name_en" => "Transport on Sells",
            "name_es" => "Transport on Sells",
            "description" => "Transport on Sells",
            "warehouse_type" => null,
            "allow_weight" => true,
            "allow_price" => true,
            "allow_package_type" => false,
            "allow_warehouse" => false
        ]);

        $warehouse_category_operation_emballage =  WarehouseCategoryOperation::create([
            "name_fr" => "Emballage",
            "name_en" => "Emballage",
            "name_es" => "Emballage",
            "description" => "Emballage",
            "warehouse_type" => "PackageType",
            "allow_weight" => false,
            "allow_price" => true,
            "allow_package_type" => true,
            "allow_warehouse" => true
        ]);

        $warehouse_category_operation_commission =  WarehouseCategoryOperation::create([
            "name_fr" => "Commission",
            "name_en" => "Commission",
            "name_es" => "Commission",
            "description" => "Commission for suppliers",
            "warehouse_type" => null,
            "allow_weight" => false,
            "allow_price" => true,
            "allow_package_type" => false,
            "allow_warehouse" => false
        ]);

        #endregion Charge Detail Categories Seeders

        #region sell_category
        $sell_category =  SellCategory::factory()->create([
            "description" => "Vente Service Congelation",
            "name_en" => "Vente Service Congelation",
            "name_fr" => "Vente Service Congelation",
            "name_es" => "Vente Service Congelation",
        ]);
        #endregion sell_category

        #endregion Fixed Charges and Type Charges Seeders

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
            $warehouse_category_local = WarehouseCategory::create([
                "name" => "local",
                "description"=>"its meaen that is local warehouse of our production"
            ]);
            $warehouse_category_foreign = WarehouseCategory::create([
                "name" => "foreign",
                "description" => "its meaen that is foreign warehouse of our production in other company or unite fish production"
            ]);
            $warehouse_category_client = WarehouseCategory::create([
                "name" => "client",
                "description" => "its meaen that is local warehouse thta hold his fishes and goods"
            ]);
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                $count = Warehouse::where("name", $data['name_chambre'])->count();
                $warehouse_category_id = $warehouse_category_client->id;
                if("CHAMBRE AKF 2" == $data['name_chambre']){
                    $warehouse_category_id = $warehouse_category_local->id;
                }
                $warehouse_type = str_contains($data['name_chambre'], "DECHET") ? "GuanoType" : "FishType";
                if ($count == 0) {
                    Warehouse::create([
                        "id" => $data['id'],
                        "name" => $data['name_chambre'],
                        "warehouse_category_id" => $warehouse_category_id,
                        "warehouse_type"=>$warehouse_type
                    ]);
                }else{
                    Warehouse::create([
                        "id" => $data['id'],
                        "name" => $data['name_chambre']."_".rand(),
                        "warehouse_category_id" => $warehouse_category_id,
                    ]);
                }
            }

            $warehouses = Warehouse::all();

            foreach ($warehouses as $warehouse) {
                foreach (FishType::all() as $fish_type) {
                    $warehouse->warehouse_stockables()->create([
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

                if (FishType::where("name_fr", $data["name_type"])->count() == 0) {
                    $fish_process_type =  FishProcessType::Where("name",$data["process"])->first();
                    $fish_process_type_id = null;
                    if($fish_process_type){
                        $fish_process_type_id = $fish_process_type->id;
                    }else{
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

        // return ;
            $path = ('storage/csv/guano_usines.csv');

            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                $charge = Charge::create([
                    "date" => $data["date_sortie"],
                    "tracking_numbers" => [(string)$data["num_bon_sortie"]],
                    "origin_id" => null,
                    "fishery_id" => null,
                    "charge_category_id" => $charge_category_guano_production->id,
                    "ship_id" => null,
                ]);
                foreach (json_decode($data["matricules"]) as $truck) {
                    $truck = Truck::where("registration_plate", $truck)->first();
                    if($truck){
                        $charge->trucks()->attach($truck);
                    }
                }
                $suppliers = Supplier::find($data["provider_id"]);
                $charge->suppliers()->attach($suppliers);
                foreach (json_decode($data["types_sortie"]) as $item) {
                    $warehouse = Warehouse::find(35);
                    $search_item = $item->type_id;
                    $item_type_id = collect([
                        [
                            "id"=>1,
                            "name"=>"Entier",
                        ],
                        [
                            "id"=>2,
                            "name"=>"HGT",
                        ],
                        [
                            "id"=>3,
                            "name"=>"HG",
                        ],
                        [
                            "id"=>4,
                            "name"=>"Filet",
                        ],
                    ])->first(function ($item) use ($search_item) {
                        return strcasecmp($item['name'], $search_item) === 0;
                    })['id'];
                    $warehouse_stockable = $warehouse->warehouse_stockables()->
                    whereHasMorph('stockable', [class_basename(GuanoType::class)], fn($query)=> $query->where('stockable_id', $item_type_id))
                    ->first();
                    
                    if (isset($warehouse_stockable)==false) {

                        $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                            "unit_price" => 0.00,
                            "total_weight" => 0.00,
                            "total_price" => 0.00,
                            "total_package_count" => 0.00,
                            "warehouse_id" => $warehouse->id,
                            "stockable_id" => $item_type_id,
                            "stockable_type" => class_basename(GuanoType::class)
                        ]);
                    }
                    $warehouse_stockable_tracking_number = null;
                    if ($warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $data['num_bon_sortie'])->count() != 1) {
                        $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $data['num_bon_sortie']);
                    }
                    $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                        "tracking_numbers" => [(string)$data['num_bon_sortie']],
                        "unit_price" => 0.00,
                        "total_weight" => 0.00,
                        "total_price" => 0.00,
                        "total_package_count" => 0.00,
                        "warehouse_stockable_id" => $warehouse_stockable->id,
                    ]);
    
                    $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                        "type_operation" => "in",
                        "date" => $charge->date,
                        "unit_price" => $item->pu,
                        "total_weight" => $item->weight,
                        "total_price" => $item->weight * $item->pu,
                        "warehouse_category_operation_id" => $warehouse_category_operation_guano_production->id,
                        "stockable_id" => $item_type_id,
                        "stockable_type" => class_basename(GuanoType::class)
                    ]);
                    // $warehouse->calc();
                    $charge->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                }
                $charge->calc();
            }
            
            $path = ('storage/csv/frais.csv');
    
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                $charge = Charge::create([
                    "date" => $data["date_reception"],
                    "tracking_numbers" => json_decode($data["num_bon_receptions"]),
                    "origin_id" => $data["origin_id"],
                    "fishery_id" => $data["place_id"],
                    "charge_category_id" => $charge_category_frais->id,
                    "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
                ]);
    
                $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
                $charge->suppliers()->attach($suppliers);

                if (count(json_decode($data["providers"])) == 0) {
                    $suppliers = Supplier::find(rand(1, 8));
                    $charge->suppliers()->attach($suppliers);
                }
                foreach (json_decode($data["matricules"]) as $truck) {
                    $truck = Truck::where("registration_plate", $truck)->first();
                    if ($truck) {
                        $charge->trucks()->attach($truck);
                    }
                }
                foreach (json_decode($data["types_reception"]) as $item) {
                    $charge->non_warehouse_stockable_operations()->create([
                        "date" => $data["date_reception"],
                        "stockable_type" => class_basename(FishType::class),
                        "stockable_id" => $item->type_id,
                        "package_type_id" => null,
    
                        "unit_price" => $item->price,
                        "total_weight" => $item->weight,
                        "total_price" => ($item->price * $item->weight),
                        "total_package_count" => $item->type_id,
                        "warehouse_category_operation_id" => $warehouse_category_operation_frais_mp->id,
                    ]);
                }
                $charge->calc();
            }

            $path = ('storage/csv/congeles.csv');
    
            $rows = CsvReader::read($path);
    
            foreach ($rows as $data) {
                $charge = Charge::create([
                    "date" => $data["date_reception"],
                    "tracking_numbers" => json_decode($data["num_bon_receptions"]),
                    "origin_id" => $data["origin_id"],
                    "fishery_id" => $data["place_id"],
                    "charge_category_id" => $charge_category_congele->id,
                    "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
                ]);

                foreach (json_decode($data["matricules"]) as $truck) {
                    $truck = Truck::where("registration_plate", $truck)->first();
                    if ($truck) {
                        $charge->trucks()->attach($truck);
                    }
                }

                $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
                $charge->suppliers()->attach($suppliers);
                
                if(count(json_decode($data["providers"])) == 0){
                    $suppliers = Supplier::find(rand(1,8));
                    $charge->suppliers()->attach($suppliers);
                }

                foreach (json_decode($data["types_production"]) as $item) {
                    $warehouse = Warehouse::find($item->chambre_id);
                    $warehouse_stockable = $warehouse->warehouse_stockables()->
                    whereHasMorph('stockable', [class_basename(FishType::class)], fn($query)=> $query->where('stockable_id', $item->type_id))
                    ->first();
                    
                    if (isset($warehouse_stockable)==false) {
                        $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                            "unit_price" => 0.00,
                            "total_weight" => 0.00,
                            "total_price" => 0.00,
                            "total_package_count" => 0.00,
                            "warehouse_id" => $warehouse->id,
                            "stockable_id" => $item->type_id,
                            "stockable_type" => class_basename(FishType::class)
                        ]);
                    }

                    $warehouse_stockable_tracking_number = null;
                    if ($warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                        $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                    }
                    $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                        "tracking_numbers" => [(string)$item->num_bon_reception],
                        "unit_price" => 0.00,
                        "total_weight" => 0.00,
                        "total_price" => 0.00,
                        "total_package_count" => 0.00,
                        "warehouse_stockable_id" => $warehouse_stockable->id,
                    ]);
    
                    $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                        "type_operation" => "in",
                        "date" => $charge->date,
                        "unit_price" => $item->pu,
                        "total_weight" => $item->total_weight_container,
                        "total_price" => $item->total_weight_container * $item->pu,
                        "total_package_count" => round($item->total_weight_container / PackageType::find($item->container_id)->size, 2),
                        "package_type_id" => $item->container_id,
                        "warehouse_category_operation_id" => $warehouse_category_operation_congele_achete->id,
                        "stockable_id" => $item_type_id,
                        "stockable_type" => class_basename(FishType::class)

                    ]);
                    // $warehouse->calc();
                    $charge->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                }
                $charge->calc();
            }
    
            $path = ('storage/csv/congelation_clients.csv');
   
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                $sell = Sell::create([
                    "date" => $data["date_reception"],
                    "tracking_numbers" => json_decode($data["num_bon_receptions"]),
                    "origin_id" => $data["origin_id"],
                    "fishery_id" => $data["place_id"],
                    "sell_category_id" => $sell_category->id,
                    "ship_id" => Ship::where("name", $data["name_ship"])->first()->id ?? null,
                ]);
                foreach (json_decode($data["matricules"]) as $truck) {
                    $truck = Truck::where("registration_plate", $truck)->first();
                    if ($truck) {
                        $sell->trucks()->attach($truck);
                    }
                }
                $clients = Client::find([$data["provider_id"]]);
                foreach (json_decode($data["types_production"]) as $item) {
                    $warehouse = Warehouse::find($item->chambre_id);
                    $warehouse_stockable = $warehouse->warehouse_stockables()->
                    whereHasMorph('stockable', [class_basename(FishType::class)], fn($query)=> $query->where('stockable_id', $item->type_id))
                    ->first();
                    
                    if (isset($warehouse_stockable)==false) {
                        $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                            "unit_price" => 0.00,
                            "total_weight" => 0.00,
                            "total_price" => 0.00,
                            "total_package_count" => 0.00,
                            "warehouse_id" => $warehouse->id,
                            "stockable_id" => $item->type_id,
                            "stockable_type" => class_basename(FishType::class)
                        ]);
                    }

                    $warehouse_stockable_tracking_number = null;
                    if ($warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                        $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                    }
                    $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                        "tracking_numbers" => [(string)$item->num_bon_reception],
                        "unit_price" => 0.00,
                        "total_weight" => 0.00,
                        "total_price" => 0.00,
                        "total_package_count" => 0.00,
                        "warehouse_stockable_id" => $warehouse_stockable->id,

                ]);
    
                $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                    "type_operation" => "in",
                    "date" => $sell->date,
                    "unit_price" => $item->pu,
                    "total_weight" => $item->total_weight_container,
                    "total_price" => $item->total_weight_container * $item->pu,
                    "total_package_count" => round($item->total_weight_container / PackageType::find($item->container_id)->size, 2),
                    "package_type_id" => $item->container_id,
                    "warehouse_category_operation_id" => $warehouse_category_operation_congele_achete->id,
                    "stockable_id" => $item_type_id,
                    "stockable_type" => class_basename(FishType::class)

                ]);
                // $warehouse->calc();
                $sell->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                }
                $sell->calc();
            }
    
            $path = ('storage/csv/stock_exits.csv');
    
            $sell_category_sortie_client =  SellCategory::factory()->create([
                "description" => "Sortie Stock Client",
                "name_en" => "Sortie Stock Client",
                "name_fr" => "Sortie Stock Client",
                "name_es" => "Sortie Stock Client",
            ]);
    
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                echo $data['id']."\n";
                $sell = Sell::create([
                    "date" => $data["date_sortie"],
                    "tracking_numbers" => json_decode($data["num_bon_sorties"]),
                    "origin_id" => $data["origin_id"]??1,
                    "fishery_id" => $data["place_id"]??1,
                    "sell_category_id" => $sell_category_sortie_client->id,
                    "ship_id" => null,
                ]);
                foreach (json_decode($data["matricules"]) as $truck) {
                    $truck = Truck::where("registration_plate", $truck)->first();
                    if ($truck) {
                        $sell->trucks()->attach($truck);
                    }
                }
                $clients = Client::find([$data["provider_id"]]);
                $sell->clients()->attach($clients);
                foreach (json_decode($data["types_sortie"]) as $item) {
                    $warehouse = Warehouse::find($item->chambre_id);
                    $warehouse_stockable = $warehouse->warehouse_stockables()->whereHasMorph('stockable', [class_basename(FishType::class)], fn($query) => $query->where('stockable_id', $item->type_id))
                        ->first();

                    if (isset($warehouse_stockable) == false) {
                        $warehouse_stockable = $warehouse->warehouse_stockables()->create([
                            "unit_price" => 0.00,
                            "total_weight" => 0.00,
                            "total_price" => 0.00,
                            "total_package_count" => 0.00,
                            "warehouse_id" => $warehouse->id,
                            "stockable_id" => $item->type_id,
                            "stockable_type" => class_basename(FishType::class)
                        ]);
                    }

                    $warehouse_stockable_tracking_number = null;
                    if ($warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception)->count() != 1) {
                        $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->whereJsonContains("tracking_numbers", $item->num_bon_reception);
                    }
                    $warehouse_stockable_tracking_number = $warehouse_stockable->warehouse_stockable_tracking_numbers()->create([
                        "tracking_numbers" => [(string)$item->num_bon_reception],
                        "unit_price" => 0.00,
                        "total_weight" => 0.00,
                        "total_price" => 0.00,
                        "total_package_count" => 0.00,
                        "warehouse_stockable_id" => $warehouse_stockable->id,

                    ]);

                    $s_f_t_n_o = $warehouse_stockable_tracking_number->warehouse_stockable_tracking_number_operations()->create([
                        "type_operation" => "out",
                        "date" => $sell->date,
                        "unit_price" => $item->pu,
                        "total_weight" => $item->weight,
                        "total_price" => $item->weight * $item->pu,
                        "total_package_count" => round($item->weight / PackageType::find($item->container_id)->size, 2),
                        "package_type_id" => $item->container_id,
                        "warehouse_category_operation_id" => $warehouse_category_operation_congele_achete->id,
                        "stockable_id" => $item_type_id,
                        "stockable_type" => class_basename(FishType::class)

                    ]);
                    // $warehouse->calc();
                    $sell->warehouse_stockable_tracking_number_operations()->save($s_f_t_n_o);
                }
                $sell->calc();
            }

        #endregion init    



        Warehouse::each(fn($q)=>$q->calc());

    }
}
