<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DBController extends Controller
{

    public static function saveDiscount($orderId, int|array $allDiscounts)
    {

        foreach ($allDiscounts as $allDiscount) {
            $discountId = $allDiscount['discount_id'];
            $discountName = $allDiscount['discount_name'];
            $discountTotal = $allDiscount['discount_price'];
            $discountSubTotal = $allDiscount['after_discount_price'];
            DB::insert("INSERT INTO discount_details (discount_id,order_id,discount_name,discount_total,sub_total) VALUES ($discountId,$orderId,'$discountName',$discountTotal,$discountSubTotal)");
            $definedDiscounts[] = $discountId;
        }
        if(count($definedDiscounts) > 0){
            $definedDiscountsStr = implode(",",$definedDiscounts);
            DB::delete("delete from discount_details where order_id = '$orderId' and discount_id NOT IN ($definedDiscountsStr)");
        }

    }

    public static function clearOldData(mixed $orderId)
    {
        DB::delete("delete from orders where id = '$orderId'");
        DB::delete("delete from order_items where order_id = '$orderId'");
        DB::delete("delete from discount_details where order_id = '$orderId'");
    }
}
