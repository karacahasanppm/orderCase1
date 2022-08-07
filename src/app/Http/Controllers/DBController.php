<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DBController extends Controller
{
    public static function getProductInfo($productId){
        return DB::selectOne("select * from products where id = '$productId'");
    }

    public static function saveOrder($item)
    {
        $orderId = $item['id'];
        $customerId = $item['customer_id'];
        $totalPrice = $item['total_price'];
        $discountedPrice = $item['discounted_price'];
        $res = DB::insert("INSERT INTO orders (id,customer_id,total_price,discounted_price) VALUES ($orderId,$customerId,$totalPrice,$discountedPrice)");

        if($res){
            return true;
        }else{
            return false;
        }

    }
    public static function saveOrderLineItems($orderId,$item)
    {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $totalCount = $item['total_unit_price'];

        $res = DB::insert("INSERT INTO order_items (order_id,product_id,quantity,unit_price,total_price) VALUES ($orderId,$productId,$quantity,$unitPrice,$totalCount)");

        if($res){
            return true;
        }else{
            return false;
        }

    }
    public static function getDiscountIds()
    {
        return DB::select("select distinct(discount_id) from discounts");
    }
    public static function getDiscountDetails($id,$discountKey)
    {
        return DB::selectOne("select discount_key_value,discount_name from discounts where discount_id = '$id' AND discount_key_name = '$discountKey'");
    }

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
