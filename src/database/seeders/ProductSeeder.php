<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0;$i < 50;$i++){
            $product = new Product([
                'name' => "Deneme Ürün $i",
                'category' => rand(1,3),
                'stock' => rand(0,60),
                'price' => rand(5,900)
            ]);
            $product->save();
        }
    }
}
