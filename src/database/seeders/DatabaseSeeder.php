<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\ProductSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\DiscountSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ProductSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
