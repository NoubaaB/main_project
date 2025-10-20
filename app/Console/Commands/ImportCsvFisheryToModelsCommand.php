<?php

namespace App\Console\Commands;

use App\Models\Fishery;
use App\Support\CsvReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvFisheryToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:fishery {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/places.csv');

        try {
            $rows = CsvReader::read($path);
            foreach ($rows as $data) {
                Fishery::creat([
                    "name" => $data["name_place"],
                ]);
            }

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:fish_familly');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
