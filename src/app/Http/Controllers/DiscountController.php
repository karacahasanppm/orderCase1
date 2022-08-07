<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public static function discount($id,$items){

        switch ($id) {
            case 1:
                $orderTotalPrice = 0;
                $discountKeys = array(
                    'minimum_total_price',
                    'total_price_discount_percent'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = DBController::getDiscountDetails($id,$discountKey);
                    $discountKeyValues[$discountKey] = $discountValues->discount_key_value;
                }
                $discountName = $discountValues->discount_name;

                foreach ($items as $item) {

                    $itemTotalPrice = $item['quantity'] * $item['unit_price'];

                    $orderTotalPrice += $itemTotalPrice;

                }

                if($orderTotalPrice > $discountKeyValues['minimum_total_price']){
                    return array(
                        'discount_id' => 1,
                        'discount_name' => $discountName,
                        'discount_price' => ($orderTotalPrice / 100) * $discountKeyValues['total_price_discount_percent'],
                        'after_discount_price' => $orderTotalPrice - (($orderTotalPrice / 100) * $discountKeyValues['total_price_discount_percent']),
                        'before_discount_price' => $orderTotalPrice
                    );
                }else{
                    return 0;
                }
            case 2:

                $orderTotalPrice = 0;
                $haveDiscount = 0;
                $orderTotalDiscountPrice = 0;
                $exceptedCategoryItems = [];
                $discountKeys = array(
                    'category_name',
                    'minimum_qty',
                    'free_product_count'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = DBController::getDiscountDetails($id,$discountKey);
                    $discountKeyValues[$discountKey] = $discountValues->discount_key_value;
                }
                $discountName = $discountValues->discount_name;
                foreach ($items as $item) {

                    $itemTotalPrice = $item['quantity'] * $item['unit_price'];

                    $orderTotalPrice += $itemTotalPrice;

                    if($item['product_category'] == $discountKeyValues['category_name']){
                        if(isset($exceptedCategoryItems[$item['product_id']])){
                            $exceptedCategoryItems[$item['product_id']]['quantity'] += $item['quantity'];
                            $exceptedCategoryItems[$item['product_id']]['unit_price'] += $item['unit_price'];
                        }else{
                            $exceptedCategoryItems[$item['product_id']]['quantity'] = $item['quantity'];
                            $exceptedCategoryItems[$item['product_id']]['unit_price'] = $item['unit_price'];
                        }

                    }

                }

                foreach ($exceptedCategoryItems as $key => $exceptedCategoryItem) {

                    if($exceptedCategoryItem > $discountKeyValues['minimum_qty']){
                        $orderTotalDiscountPrice += $exceptedCategoryItem['unit_price'];
                        $haveDiscount = 1;
                    }

                }

                if($haveDiscount == 1){
                    return array(
                        'discount_id' => $id,
                        'discount_name' => $discountName,
                        'discount_price' => $orderTotalDiscountPrice,
                        'after_discount_price' => $orderTotalPrice - $orderTotalDiscountPrice,
                        'before_discount_price' => $orderTotalPrice
                    );
                }else{
                    return 0;
                }

            case 3:
                $orderTotalPrice = 0;
                $exceptedCategoryItems = 0;
                $discountKeys = array(
                    'category_name',
                    'minimum_qty',
                    'total_price_discount_percent'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = DBController::getDiscountDetails($id,$discountKey);
                    $discountKeyValues[$discountKey] = $discountValues->discount_key_value;
                }
                $discountName = $discountValues->discount_name;
                foreach ($items as $item) {

                    $itemTotalPrice = $item['quantity'] * $item['unit_price'];

                    $orderTotalPrice += $itemTotalPrice;

                    if($item['product_category'] == $discountKeyValues['category_name']){

                        $exceptedCategoryItems += $item['quantity'];

                    }

                }

                if($exceptedCategoryItems >= 2){
                    return array(
                        'discount_id' => $id,
                        'discount_name' => $discountName,
                        'discount_price' => min(array_column($items,'unit_price')),
                        'after_discount_price' => $orderTotalPrice - min(array_column($items,'unit_price')),
                        'before_discount_price' => $orderTotalPrice
                    );
                }else{
                    return 0;
                }

        }

    }
}
