<?php

namespace Database\Seeders;

use App\Models\Dream;
use App\Models\User;
use Illuminate\Database\Seeder;

class DreamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::chunk(100, function ($users) {
            $users->each(function ($user) {

                // random number of dreams between 1 and 10
                Dream::factory()
                    ->count(rand(1, 10))
                    ->for($user)
                    ->create();

            });
        });

    }
}
