<?php

namespace App\Console\Commands;

use App\Models\FishType;
use App\Models\Warehouse;
use App\Support\CsvReader;
use App\Models\TypeWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvWarehousesToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:warehouse {path?}';

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
        $path = $this->argument('path') ?? 'storage/csv/chambres.csv';
        TypeWarehouse::factory(3)->create();
        $warehouses = Warehouse::factory(10)->create();

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
        
        try {
            $rows = CsvReader::read($path);

            foreach ($rows as $data) {
                if(Warehouse::where("name",$data['name_chambre'])->count()==0){
                    Warehouse::create([
                        "name"=>$data['name_chambre']
                    ]);
                }
            }

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:origin');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
