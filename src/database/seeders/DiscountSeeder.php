<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $discount = new Discount([
            'id' => 1,
            'discount_id' => 1,
            'discount_name' => "TOTAL_PRICE_DISCOUNT",
            'discount_key_name' => "minimum_total_price",
            'discount_key_value' => "1000"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 2,
            'discount_id' => 1,
            'discount_name' => "TOTAL_PRICE_DISCOUNT",
            'discount_key_name' => "total_price_discount_percent",
            'discount_key_value' => "10"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 3,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_DISCOUNT",
            'discount_key_name' => "category_name",
            'discount_key_value' => "2"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 4,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_DISCOUNT",
            'discount_key_name' => "minimum_qty",
            'discount_key_value' => "6"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 5,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_DISCOUNT",
            'discount_key_name' => "free_product_count",
            'discount_key_value' => "1"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 6,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_TOTAL_DISCOUNT",
            'discount_key_name' => "category_name",
            'discount_key_value' => "1"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 7,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_TOTAL_DISCOUNT",
            'discount_key_name' => "minimum_qty",
            'discount_key_value' => "2"
        ]);
        $discount->save();
        $discount = new Discount([
            'id' => 8,
            'discount_id' => 2,
            'discount_name' => "CATEGORY_QTY_TOTAL_DISCOUNT",
            'discount_key_name' => "total_price_discount_percent",
            'discount_key_value' => "10"
        ]);
        $discount->save();
    }
}
