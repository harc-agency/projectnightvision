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
        // User::factory(10)->create();

        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => bcrypt('asdfasdf'), // Ensure this matches your password hashing
                'remember_token' => Str::random(10),
            ]
        );

        User::factory(10)->create();

        $this->call([
            DreamSeeder::class,
        ]);

        // symbol seeder
        $this->call([
            SymbolSeeder::class,
        ]);
    }
}
