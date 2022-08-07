<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountDetail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{

    public function getAllDiscounts()
    {
        $discounts = DiscountDetail::select('order_id')->distinct()->get();
        if(count($discounts) > 0){

            foreach ($discounts as $discount) {

                $discountRequest = new \Illuminate\Http\Request();
                $discountRequest->merge(['id' => $discount->order_id]);
                $discountDetails = $this->getDiscountByOrderId($discountRequest,true);

                $result['discounts'][] = $discountDetails;

            }

            return response()->json([
                'result' => $result
            ],200);

        }else{
            return response()->json([],204);
        }
    }

    public function getDiscountByOrderId(Request $request,$inner = false)
    {

        $validateRequest = Validator::make($request->all(),[
            'id' => 'required|integer|exists:orders,order_id'
        ],[

            'id.exists' => array('field' => ':attribute','message' => 'This order not found in databases.'),

        ]);

        if(count($validateRequest->errors()) > 0){
            return response()->json([
                'result' => array(
                    'errors' => $validateRequest->errors()
                )
            ],400);
        }

        $orderId = $request->input('id');
        $totalPrice = Order::where('order_id', $orderId)->first()->total_price;
        $totalDiscountPrice = Order::where('order_id', $orderId)->first()->discounted_price;
        $orderDiscounts = DiscountDetail::where('order_id',$orderId)->orderByDesc('sub_total')->get();
        foreach ($orderDiscounts as $orderDiscount) {

            $discountName = $orderDiscount->discount_name;
            $discountTotal = $orderDiscount->discount_total;
            $discountSubTotal = $orderDiscount->sub_total;

            $discounts[] = array(
                'discountReason' => $discountName,
                'discountAmount' => $discountTotal,
                'subtotal' => $discountSubTotal
            );

        }

        if($inner){
            return array(
                'order_id' => $orderId,
                'discounts' => $discounts,
                'totalDiscount' => $totalDiscountPrice,
                'discountedTotal' => $totalPrice - $totalDiscountPrice
            );
        }

        if(count($discounts) > 0){
            return response()->json([
                'result' => array(
                    'order_id' => $orderId,
                    'discounts' => $discounts,
                    'totalDiscount' => $totalDiscountPrice,
                    'discountedTotal' => $totalPrice - $totalDiscountPrice
                )
            ],200);
        }


    }

    public static function discount($id,$items,$calculatedTotalPrice){

        switch ($id) {
            case 1:
                $orderTotalPrice = $calculatedTotalPrice;
                $discountKeys = array(
                    'minimum_total_price',
                    'total_price_discount_percent'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = Discount::where('discount_id',$id)->where('discount_key_name',$discountKey)->first()->discount_key_value;
                    $discountKeyValues[$discountKey] = $discountValues;
                }
                $discountName = Discount::where('discount_id',$id)->first()->discount_name;

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

                $orderTotalPrice = $calculatedTotalPrice;
                $haveDiscount = 0;
                $orderTotalDiscountPrice = 0;
                $exceptedCategoryItems = [];
                $discountKeys = array(
                    'category_name',
                    'minimum_qty',
                    'free_product_count'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = Discount::where('discount_id',$id)->where('discount_key_name',$discountKey)->first()->discount_key_value;
                    $discountKeyValues[$discountKey] = $discountValues;
                }
                $discountName = Discount::where('discount_id',$id)->first()->discount_name;
                foreach ($items as $item) {

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
                $orderTotalPrice = $calculatedTotalPrice;
                $exceptedCategoryItems = 0;
                $discountKeys = array(
                    'category_name',
                    'minimum_qty',
                    'total_price_discount_percent'
                );
                foreach ($discountKeys as $discountKey) {
                    $discountValues = Discount::where('discount_id',$id)->where('discount_key_name',$discountKey)->first()->discount_key_value;
                    $discountKeyValues[$discountKey] = $discountValues;
                }
                $discountName = Discount::where('discount_id',$id)->first()->discount_name;
                foreach ($items as $item) {

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
