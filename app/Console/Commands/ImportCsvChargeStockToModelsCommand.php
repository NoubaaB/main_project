<?php

namespace App\Console\Commands;

use App\Models\Charge;
use App\Models\Ship;
use App\Models\Supplier;
use App\Models\TypeCharge;
use App\Support\CsvReader;
use Illuminate\Console\Command;

class ImportCsvChargeStockToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:charge_warehouse {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $path = $this->argument('path') ?? ('storage/csv/frais.csv');

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

        try {
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                // dd(json_decode($data["types_reception"]));
                $charge = Charge::create([
                    "date"=> $data["date_reception"],
                    "tracking_numbers"=> $data["num_bon_receptions"],
                    "origin_id"=> $data["origin_id"],
                    "fishery_id"=> $data["place_id"],
                    "type_charge_id"=> $type_charge->id,
                    "ship_id"=> Ship::where("name",$data["name_ship"])->first()->id ?? null,
                ]);
                
                $suppliers = Supplier::find(collect(json_decode($data["providers"]))->pluck('id')->toArray());
                $charge->suppliers()->save($suppliers);
                foreach (json_decode($data["types_reception"]) as $item) {
                    $charge->non_warehouse_charge_items()->create([
                        "fish_type_id"=>$item->type_id,
                        "package_id"=>null,

                        "unit_price"=>$item->price,
                        "total_weight"=>$item->weight,
                        "total_price"=>($item->price*$item->weight),
                        "total_package_count"=>$item->type_id,
                    ]);
                }
            }

            $this->info("CSV imported successfully from: $path");
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
