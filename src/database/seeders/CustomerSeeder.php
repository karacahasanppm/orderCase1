<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0;$i < 50;$i++){
            $user = new Customer([
                'name' => Str::random(10),
                'since' => date("Y-m-d H:i:s",mt_rand(1262055681,1262055681)),
                'revenue' => 0
            ]);
            $user->save();
        }
    }
}
