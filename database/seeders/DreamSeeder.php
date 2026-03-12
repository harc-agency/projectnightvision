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
                Dream::factory()
                    ->count(random_int(1, 10))
                    ->for($user)
                    ->create();
            });
        });

        $this->call(PublicDreamGlobeSeeder::class);
    }
}
