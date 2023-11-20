<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Scrap;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!Scrap::first())
        {
            Scrap::factory(1)->create();
        }

         if (!User::first())
         {
             User::factory()->create([
                 'name' => 'Admin User',
                 'email' => 'admin@ecoteers.nl',
             ]);
         }
    }
}
