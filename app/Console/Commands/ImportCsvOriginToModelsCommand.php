<?php

namespace App\Console\Commands;

use App\Models\Origin;
use App\Support\CsvReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvOriginToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:origin {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/origins.csv');

        try {
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                Origin::creat([
                    "name" => $data["name_origin"],
                ]);
            }

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:matricule_ship');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
