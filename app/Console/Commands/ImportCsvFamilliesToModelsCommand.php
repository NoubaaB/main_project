<?php

namespace App\Console\Commands;

use App\Support\CsvReader;
use App\Models\FishFamilly;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvFamilliesToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:fish_familly {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/families.csv');

        try {
            $rows = CsvReader::read($path);

            foreach ($rows as $data) {
                if (FishFamilly::where("name", $data["name"])->count() == 0) {
                    FishFamilly::create([
                        "id" => $data["id"],
                        "name" => $data["name"]
                    ]);
                }
            }

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:package');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }        
    }
}
