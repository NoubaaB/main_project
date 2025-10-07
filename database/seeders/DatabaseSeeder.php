<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Stock;
use App\Models\Origin;
use App\Models\Fishery;
use App\Models\Package;
use App\Models\FishType;
use App\Models\Supplier;
use App\Models\TypeCharge;
use App\Models\FishFamilly;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Charge;
use App\Models\ChargeDetail;
use App\Models\StockDetail;
use App\Models\StockDetailOperation;
use App\Models\StockFishDetail;

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

        Supplier::factory(10)->create();


        $fish_famillies = [
            "MAQUERAUX",
            "SARDINES",
            "CHINCHARDS"
        ];

        $fish_familly = FishFamilly::factory()->create([
            "name" => $fish_famillies[0]
        ]);

        $fish_types = [
            "MCM_CONGELE",
            "MCL_CONGELE",
            "MCMX_CONGELE",
            "MCS_CONGELE",
            "MCSS_CONGELE",
            "MCSSS_CONGELE 20 kg",
            "MCXL_CONGELE",
            "MFL_FRAIS",
            "MFM_FRAIS",
            "MFS_FRAIS",
            "MFSS_FRAIS",
            "MFSSS_FRAIS",
            "MAQ FILET MORCEAU",
            "MCSS HGT",
            "MCSSS_SOUL 12 kg",
            "MCSSS CARTON 24",
            "MAQUEREAU MIXTE FRAIS",
            "MAQUEREAU SOUL",
            "MFSSSS_FRAIS",
            "MAQ IQF",
            "MAQ S SOUL",
            "MAQUEREAU HGT SOUL",
            "MAQUEREAU M SOUL CONGELE",
            "MAQ HGT B",
            "MAQ BLOCK",
            "MAQ FRAIS L",
            "MAQ FRAIS M",
            "MAQ FRAIS S",
            "MAQ FRAIS 2S",
            "MAQ FILET"
        ];
        
        foreach ($fish_types as $f_type) {
            FishType::factory()->create([
                "name" => $f_type,
                "scientific_name" => $f_type,
                "fish_familly_id" => $fish_familly->id
            ]);
        }

        $fish_types=[
            "SCE A SOUL CONGELE",
            "SCE A_CONGELE S01",
            "SCE B_CONGELE S01",
            "SCN A _CONGELE 22 Kg",
            "SCN B CONGELE 20 Kg",
            "SARDINE SOUL 12 kg",
            "APPA 20 Kg",
            "SCN A CONGLE 20 Kg",
            "SCN B CONGELE 21 Kg",
            "SCE B SOUL CONGELE",
            "SFN B S01",
            "SFN B S02",
            "APPA 24 KG",
            "APPA 21KG",
            "SCE C_CONGELE S01",
            "SAR ENT 22 KG",
            "SAR ENT 21 KG",
            "SAR IQF",
            "SFN A S01",
            "SCE A_CONGELE S02",
            "SCE B_CONGELE S02",
            "SFN A S02",
            "SCE C_CONGELE S02",
            "SCN A CONGLE 21Kg",
            "SCN B CONGELE 22kg",
            "SARDINE SOUL 13 kg",
            "SFN C S01",
            "SFN C S02",
            "SCE C SOUL CONGELE",
            "SCE A-CONGELE S01",
            "SCE (A-)CONGELE S02",
            "SCN APPA _CONGELE 22 Kg",
            "SAR ENT 20 KG",
            "SAR ENT 10KG",
            "SCN A _CONGELE 24Kg",
            "SCE C- CONGELE S03",
            "SCE A+ CONGELE S03",
            "SCE A CONGELE S03",
            "SCE B CONGELE S03",
            "SCE C CONGELE S03",
            "SARDINE FILET",
            "SCE A ECAILLES CONG",
            "SAR IQF SP A+",
            "SAR IQF SP A",
            "SAR IQF SM A",
            "SAR IQF SM A+",
            "SAR 2E QA CONGELE HG",
            "SAR 2E QC_ CONGELE HG",
            "SAR IQF SP C",
            "SAR IQF SP B",
            "SAR BLOCK 24 KG"
        ];

        $fish_familly = FishFamilly::factory()->create([
            "name" => $fish_famillies[1]
        ]);

        foreach ($fish_types as $f_type) {
            FishType::factory()->create([
                "name" => $f_type,
                "scientific_name" => $f_type,
                "fish_familly_id" => $fish_familly->id
            ]);
        }

        $fish_types = [
            "HCL_CONGELE",
            "HCM_CONGELE",
            "HCS_CONGELE",
            "HCSS_CONGELE",
            "HFL_FRAIS",
            "HFM_FRAIS",
            "HFS_FRAIS",
            "HFSS_FRAIS",
            "HFXL_FRAIS",
            "HCSSS_CONGELE",
            "HFSSS_FRAIS",
            "CHINCHARDS MIXTE FRAIS",
            "CHINCHARD",
            "CHINCHARD FRAIS L",
            "CHINCHARD FRAIS M",
            "CHINCHARD FRAIS S",
            "CHINCHARD FRAIS 2 S",
            "CHINCHARD CONGELE M",
            "CHINCHARD CONGELE S",
            "CHINCHARD CONGELE L",
            "CHINCHARD CONGELE 2 S",
            "CHINCHARD 3 S",
            "CHINCHARD 4 S"
        ];

        $fish_familly = FishFamilly::factory()->create([
            "name" => $fish_famillies[2]
        ]);

        foreach ($fish_types as $f_type) {
            FishType::factory()->create([
                "name" => $f_type,
                "scientific_name" => $f_type,
                "fish_familly_id" => $fish_familly->id
            ]);
        }
        #region Packages Factory
        Package::factory()->create([
            "name" => "20 KG",
            "size" => 20,
            "description" => "20 KG"
        ]);
        Package::factory()->create([
            "name" => "24 KG",
            "size" => 24,
            "description" => "24 KG"
        ]);
        Package::factory()->create([
            "name" => "22 KG",
            "size" => 22,
            "description" => "22 KG"
        ]);
        Package::factory()->create([
            "name" => "21 KG",
            "size" => 21,
            "description" => "21 KG"
        ]);
        Package::factory()->create([
            "name" => "12 KG",
            "size" => 12,
            "description" => "12 KG"
        ]);
        Package::factory()->create([
            "name" => "10 KG",
            "size" => 10,
            "description" => "10 KG"
        ]);
        #endregion
        
        Origin::factory(10)->create();

        Fishery::factory()->create([
            "name" => "Cotiere",
            "name_en" => "Cotiere",
            "name_fr" => "Cotiere",
            "name_es" => "Cotiere",
        ]);
        Fishery::factory()->create([
            "name" => "purse seining and mid-water trawling",
            "name_en" => "RSW",
            "name_fr" => "RSW",
            "name_es" => "RSW",
        ]);

        #region Type Charges Factory
        TypeCharge::factory()->create([
            "description" => "Cotiere",
            "name_en" => "Cotiere",
            "name_fr" => "Cotiere",
            "name_es" => "Cotiere",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        TypeCharge::factory()->create([
            "description" => "purse seining and mid-water trawling",
            "name_en" => "RSW",
            "name_fr" => "RSW",
            "name_es" => "RSW",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        TypeCharge::factory()->create([
            "description" => "frozen",
            "name_en" => "Frozen",
            "name_fr" => "Congelé",
            "name_es" => "Congelado",
            "allow_charge_details" => true,
            "allow_production_details" => false,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        TypeCharge::factory()->create([
            "description" => "Freezing service",
            "name_en" => "Freezing service",
            "name_fr" => "Service de congélation",
            "name_es" => "Servicio de congelación",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        TypeCharge::factory()->create([
            "description" => "buy and sell fresh fish",
            "name_en" => "Buy and sell fresh fish",
            "name_fr" => "Acheter et vendre du poisson frais",
            "name_es" => "Comprar y vender pescado fresco",
            "allow_charge_details" => true,
            "allow_production_details" => true,
            "add_wieght" => true,
            "list_type_details" => 1,
        ]);

        TypeCharge::factory()->create([
            "description" => "Emballage",
            "name_en" => "Packaging",
            "name_fr" => "Emballage",
            "name_es" => "Embalaje",
            "allow_charge_details" => false,
            "allow_production_details" => false,
            "add_wieght" => false,
            "list_type_details" => 0,
        ]);

        TypeCharge::factory()->create([
            "description" => "Transport",
            "name_en" => "Transport",
            "name_fr" => "Transport",
            "name_es" => "Transporte",
            "allow_charge_details" => false,
            "allow_production_details" => false,
            "add_wieght" => false,
            "list_type_details" => 0,
        ]);

        TypeCharge::factory()->create([
            "description" => "Autre Charge",
            "name_en" => "Other Charge",
            "name_fr" => "Autre Charge",
            "name_es" => "Otro Cargo",
            "allow_charge_details" => false,
            "allow_production_details" => false,
            "add_wieght" => false,
            "list_type_details" => 0,
        ]);

        TypeCharge::factory()->create([
            "description" => "Glace",
            "name_en" => "Ice",
            "name_fr" => "Glace",
            "name_es" => "Hielo",
            "allow_charge_details" => false,
            "allow_production_details" => false,
            "add_wieght" => false,
            "list_type_details" => 0,
        ]);

        TypeCharge::factory()->create([
            "description" => "Comission",
            "name_en" => "Commission",
            "name_fr" => "Comission",
            "name_es" => "Comisión",
            "allow_charge_details" => false,
            "allow_production_details" => false,
            "add_wieght" => false,
            "list_type_details" => 0,
        ]);
        #endregion


        $stocks = Stock::factory(10)->create();

        foreach ($stocks as $stock) {
            foreach (FishType::all() as $fish_type) {
                $stock->stock_details()->create([
                    "unit_price" => 0.00,
                    "total_weight" => 0.00,
                    "total_price" => 0.00,
                    "stock_id" => $stock->id,
                    "fish_type_id" => $fish_type->id
                ]);
            }
        }

        $type_charges = TypeCharge::all();
        foreach ($type_charges as $type_charge) {
            $charges = Charge::factory(10)->create([
                "type_charge_id" => $type_charge->id,
                "fishery_id" => Fishery::all()->random()->id,
                "origin_id" => Origin::all()->random()->id,
            ]);
            foreach ($charges as $charge) {
                if($charge->type_charge_id == 1){
                    $charge_details = ChargeDetail::factory(10)->create([
                        "fish_type_id" => FishType::all()->random()->id,
                        "charge_id" => $charge->id,
                        "package_id" => Package::all()->random()->id,
                        "stock_id" => Stock::all()->random()->id,
                    ]);

                    foreach ($charge_details as $charge_detail) {
                        $stock_detail = StockDetail::where([
                            "stock_id"=> $charge_detail->stock_id,
                            "fish_type_id"=> $charge_detail->fish_type_id
                        ])->first();

                        $charge_detail->stock_fish_detail()->create(['stock_detail_id'=>$stock_detail->id]);
                        //detailable()->save($charge_detail)
                    }
                    
                    $charge->suppliers()->sync(collect(range(1, 10))
                                            ->shuffle()
                                            ->take(rand(1, 5))
                                            ->values()
                                            ->toArray());
                    $charge->calc();
                }
            }
        }
        foreach ($stocks as $stock) {
            $stock->calc();
        }

    }
}
