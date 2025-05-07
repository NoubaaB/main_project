<?php

namespace App\Console\Commands;

use App\Models\PrimaryFishProduct;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;


class ImportExcelToModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from a hardcoded Excel file into the model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = 'C:\\Users\\pc\\Downloads\\listes_Frais.xlsx';

        if (!File::exists($path)) {
            $this->error("Excel file not found at: $path");
            return Command::FAILURE;
        }
        echo Excel::toArray(null, $path);
        // $rows = Excel::toCollection(null, $path)->first();

        // foreach ($rows as $row) {
        //     echo $row['Date BR']; // adjust column names
        //     // PrimaryFishProduct::create([
        //     //     'column1' => $row['column1'], // adjust column names
        //     //     'column2' => $row['column2'],
        //     //     // Add all necessary fields
        //     // ]);
        // }

        $this->info('> The data imported successfully!');
        return Command::SUCCESS;
    }
}
