<?php

namespace App\Console\Commands;

use App\Models\Ship;
use App\Models\Truck;
use App\Support\CsvReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvMatriculeShipToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:matricule_ship {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/ships.csv');

        try {
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

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:fish_type');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
