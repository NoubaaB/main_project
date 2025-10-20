<?php

namespace App\Console\Commands;

use App\Models\FishType;
use App\Support\CsvReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportCsvFishTypesToModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:fish_type {path?}';

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
        $path = $this->argument('path') ?? ('storage/csv/types.csv');

        try {
            $rows = CsvReader::read($path);

            foreach ($rows as $data) {

                if (FishType::where("name", $data["name_type"])->count() == 0) {
                    FishType::create([
                        "id"=>$data["id"],
                        "name" => $data["name_type"],
                        "fish_familly_id"=>$data["family_id"]== "NULL"? NULL : $data["family_id"]
                    ]);
                }
            }

            $this->info("CSV imported successfully from: $path");
            Artisan::call('csv:fishery');
            return Command::SUCCESS;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
            $this->error("Failed to open file at: $path");
            return Command::FAILURE;
        }
    }
}
