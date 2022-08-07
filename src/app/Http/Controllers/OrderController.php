<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\countOf;

class OrderController extends Controller
{

    public function addOrder(Request $request)
    {

        $errors = [];
        $successes = [];

        $fixedNames = [
            'orders' => 'orders',
            'orders.*.id' => 'id',
            'orders.*.customer_id' => 'customer_id',
            'orders.*.items' => 'items',
            'orders.*.items.*.product_id' => 'product_id',
            'orders.*.items.*.quantity' => 'quantity',
            'orders.*.items.*.unit_price' => 'unit_price'
        ];

        $validateRequest = Validator::make($request->all(),[
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.customer_id' => 'required|integer|exists:customers,id',
            'orders.*.items' => 'required|array|min:0',
            'orders.*.items.*.product_id' => 'required|integer|exists:products,id',
            'orders.*.items.*.quantity' => 'required|integer',
            'orders.*.items.*.unit_price' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/|'
        ],[

            'orders.required' => "aaa",
            'orders.*.items.*.product_id.exists' => array('field' => ':attribute','message' => 'This product not found in databases.'),
            'orders.*.customer_id.exists' => array('field' => ':attribute','message' => 'This customer not found in databases.')

        ],$fixedNames);

        if(count($validateRequest->errors()) > 0){

            $validateMessages = $validateRequest->errors()->messages();

            foreach ($validateMessages as $key => $val) {

                if($key !== 'orders'){
                    $key = explode(".",$key)[1];
                }

                foreach ($val as $item) {

                    $errors[] = array(
                        'error_description' => $item,
                        'orders_index' => (int)$key
                    );

                }

            }

            return response()->json([
                'result' => array(
                    'errors' => array_values($errors),
                    'successes' => []
                )
            ],400);

        }else{

            $orders = $request->input('orders');

            foreach ($orders as $key => $order) {
                $orderAvailability = 1;
                $orderId = $order['id'];
                $orderCustomerId = $order['customer_id'];
                $totalProductQtys = [];
                $calculatedTotalPrice = 0;
                foreach ($order['items'] as $orderDetails) {
                    if(isset($totalProductQtys[$orderDetails['product_id']])){
                        $totalProductQtys[$orderDetails['product_id']] += $orderDetails['quantity'];
                    }else{
                        $totalProductQtys[$orderDetails['product_id']] = $orderDetails['quantity'];
                    }
                }
                foreach ($order['items'] as $itemKey => $item) {

                    $orderProductId = $item['product_id'];
                    $orderProductQty = $item['quantity'];
                    $orderProductUnitPrice = $item['unit_price'];
                    $productStock = DBController::getProductInfo($item['product_id'])->stock;
                    $productCategory = DBController::getProductInfo($item['product_id'])->category;
                    $totalQtyThisProduct = $totalProductQtys[$item['product_id']];
                    if($productStock < $totalQtyThisProduct){

                        $errors[] = array(
                            'error_description' => array(
                                'order_id' => $orderId,
                                'product_id' => $orderProductId,
                                'message' => "This product have not enough stock."
                            ),
                            'error_index' => (int)$key
                        );
                        $orderAvailability = 0;
                        break;
                    }else{

                        $calculatedItemPrice = $orderProductQty * $orderProductUnitPrice;
                        $calculatedTotalPrice += $calculatedItemPrice;
                        $order['items'][$itemKey]['total_unit_price'] = $calculatedItemPrice;
                        $order['items'][$itemKey]['product_category'] = $productCategory;

                    }

                }

                if($orderAvailability === 1){

                    DBController::clearOldData($orderId);

                    $totalDiscountPrice = $this->calculateOrderDiscounts($orderId,$order['items']);

                    $saveOrderArr = array(
                        'id' => $orderId,
                        'customer_id' => $orderCustomerId,
                        'total_price' => $calculatedTotalPrice,
                        'discounted_price' => $totalDiscountPrice
                    );

                    $orderSaveRes = DBController::saveOrder($saveOrderArr);

                    if($orderSaveRes){
                        $successes[] = array(
                            'order_id' => $orderId,
                            'message' => "Order saved successfully."
                        );
                    }else{
                        $errors[] = array(
                            'error_description' => array(
                                'order_id' => $orderId,
                                'message' => "An Error Occured."
                            ),
                            'error_index' => (int)$key
                        );
                    }

                    foreach ($order['items'] as $item) {

                        DBController::saveOrderLineItems($orderId,$item);

                    }


                }

            }

        }
        return response()->json([
            'result' => array(
                'errors' => array_values($errors),
                'successes' => array_values($successes)
            )
        ],207);

    }

    public function calculateOrderDiscounts($orderId,$items)
    {
        $allDiscounts = [];
        $totalDiscountPrice = 0;
        $discountIds = DBController::getDiscountIds();
        foreach ($discountIds as $discountId) {
            $discountResult = DiscountController::discount($discountId->discount_id,$items);
            if ($discountResult != 0){
                $allDiscounts[] = $discountResult;
                $totalDiscountPrice += $discountResult['discount_price'];

            }
        }

        if(count($allDiscounts) > 0){
            DBController::saveDiscount($orderId,$allDiscounts);
        }

        return $totalDiscountPrice;


    }

}
