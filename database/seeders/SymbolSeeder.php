<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SymbolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // import ./symbols.json file
        $symbols = json_decode(file_get_contents(database_path('seeders/symbols.json')), true);
        // insert into symbols table
        \DB::table('symbols')->insert($symbols);
    }
}
