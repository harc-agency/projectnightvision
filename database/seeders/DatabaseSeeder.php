<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'holm.tanner@gmail.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => bcrypt('Harcallyenations1)'),
                'remember_token' => Str::random(10),
            ]
        );

        if (!filter_var(env('SEED_SAMPLE_DATA', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        User::factory(10)->create();

        $this->call([
            DreamSeeder::class,
        ]);
    }
}
