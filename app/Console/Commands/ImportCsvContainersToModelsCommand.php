<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Support\CsvReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvContainersToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:package {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/containers.csv');

        try {
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

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:charge_warehouse');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
