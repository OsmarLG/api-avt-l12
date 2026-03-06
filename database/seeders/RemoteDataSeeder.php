<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RemoteDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataDirectory = database_path('data');
        $files = \Illuminate\Support\Facades\File::files($dataDirectory);

        if (empty($files)) {
            $this->command->info('No JSON files found in database/data.');
            return;
        }

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $summary = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Extract table name from filename
            if (preg_match('/^(.+)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.json$/', $filename, $matches)) {
                $tableName = $matches[1];
            } else {
                continue;
            }

            $json = \Illuminate\Support\Facades\File::get($file->getPathname());
            $data = json_decode($json, true);

            if (!is_array($data)) {
                $this->command->error("Invalid JSON format in {$filename}");
                continue;
            }

            if (empty($data)) {
                $this->command->info("Skipping empty table: {$tableName}");
                $summary[$tableName] = 0;
                continue;
            }

            try {
                // Get column information to identify generated columns
                $columns = \Illuminate\Support\Facades\Schema::getColumns($tableName);
                $validColumns = [];

                foreach ($columns as $column) {
                    // Skip generated/virtual columns
                    if (isset($column['generation']) && $column['generation']) {
                        continue;
                    }
                    $validColumns[] = $column['name'];
                }

                // Filter data to only include valid columns
                $filteredData = array_map(function ($item) use ($validColumns) {
                    return array_filter($item, function ($key) use ($validColumns) {
                        return in_array($key, $validColumns);
                    }, ARRAY_FILTER_USE_KEY);
                }, $data);

                \Illuminate\Support\Facades\DB::table($tableName)->truncate();
                \Illuminate\Support\Facades\DB::table($tableName)->insert($filteredData);

                $count = count($filteredData);
                $this->command->info("Successfully seeded {$tableName} ({$count} records)");
                $summary[$tableName] = $count;
            } catch (\Exception $e) {
                $this->command->error("Error seeding table {$tableName}: " . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('-----------------------------------------');
        $this->command->info('Remote data seeding summary:');
        foreach ($summary as $table => $count) {
            $this->command->line("- {$table}: {$count} records");
        }
        $this->command->info('-----------------------------------------');
    }
}
