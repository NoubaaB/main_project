<?php

namespace Database\Seeders;

use App\Models\ChargeType;
use App\Models\Fishery;
use App\Models\FishFamilly;
use App\Models\FishType;
use App\Models\FreshFishReceive;
use App\Models\Origin;
use App\Models\Package;
use App\Models\PrimaryFishDetail;
use App\Models\Role;
use App\Models\Supplier;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        Supplier::factory(10)->create();
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

        ChargeType::factory()->create([
            "description" => "Cotiere",
            "name_en" => "Cotiere",
            "name_fr" => "Cotiere",
            "name_es" => "Cotiere",
        ]);
        ChargeType::factory()->create([
            "description" => "purse seining and mid-water trawling",
            "name_en" => "RSW",
            "name_fr" => "RSW",
            "name_es" => "RSW",
        ]);
        ChargeType::factory()->create([
            "description" => "frozen",
            "name_en" => "Frozen",
            "name_fr" => "Congelé",
            "name_es" => "Congelado",
        ]);
        ChargeType::factory()->create([
            "description" => "Freezing service",
            "name_en" => "Freezing service",
            "name_fr" => "Service de congélation",
            "name_es" => "Servicio de congelación",
        ]);
        ChargeType::factory()->create([
            "description" => "buy and sell fresh fish",
            "name_en" => "Buy and sell fresh fish",
            "name_fr" => "Acheter et vendre du poisson frais",
            "name_es" => "Comprar y vender pescado fresco",
        ]);

        $charge_types = ChargeType::all();
        foreach ($charge_types as $charge_type) {
            $fresh_fishes_receives = FreshFishReceive::factory(10)->create([
                "charge_type_id" => $charge_type->id,
                "fishery_id" => Fishery::all()->random()->id,
                "origin_id" => Origin::all()->random()->id,
                "supplier_id" => Supplier::all()->random()->id,
            ]);
            foreach ($fresh_fishes_receives as $fresh_fishes_receive) {
                if($fresh_fishes_receive->charge_type_id == 1){
                    PrimaryFishDetail::factory(10)->create([
                        "fish_type_id" => FishType::all()->random()->id,
                        "fresh_fish_receive_id" => $fresh_fishes_receive->id,
                        "package_id" => Package::all()->random()->id,
                    ]);

                }
            }

        }

    }
}
