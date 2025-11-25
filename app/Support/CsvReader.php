<?php

namespace App\Support;

class CsvReader
{
    /**
     * Read a CSV file and return the data as an array of associative arrays.
     *
     * @param string $path
     * @return array
     */
    public static function read(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found at: $path");
        }

        $data = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $obj = array_combine($header, $row);
                if($obj["deleted_at"] == "NULL"){
                    $data[] = $obj;
                }
            }

            fclose($handle);
        }

        return $data;
    }
}
