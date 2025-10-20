<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Supplier;

use App\Support\CsvReader;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvSupplierClientToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:import_supplier {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from an CSV file into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $path = $this->argument('path') ?? 'storage/csv/providers.csv';

        try {
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

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:warehouse');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            
            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }

    }
}
