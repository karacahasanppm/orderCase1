<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DBController;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\DiscountDetail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\countOf;

class OrderController extends Controller
{

    public function deleteOrder(Request $request)
    {
        $errors = [];
        $successes = [];

        $fixedNames = [
            'orders' => 'orders',
            'orders.*' => 'order_id'
        ];

        $validateRequest = Validator::make($request->all(),[
            'orders' => 'required|array',
            'orders.*' => 'required|integer|exists:orders,order_id'
        ],[

            'orders.*.exists' => array('field' => ':attribute','message' => 'This order not found in databases.'),

        ],$fixedNames);

        if(count($validateRequest->errors()) > 0){
            return response()->json([
                'result' => array(
                    'errors' => $validateRequest->errors(),
                    'successes' => []
                )
            ],400);
        }

        $request->input('orders');

        foreach ($request->input('orders') as $orderId) {

            $orderRevenue = Order::where('order_id',$orderId)->first()->discounted_price;
            $customerId = Order::where('order_id',$orderId)->first()->customer_id;
            $orderItems = OrderItem::where('order_id',$orderId)->get();
            $customerNewRevenue = Customer::where('id',$customerId)->first()->revenue - $orderRevenue;
            Customer::where('id',$customerId)->update(['revenue' => $customerNewRevenue]);
            foreach ($orderItems as $orderItem) {

                $productNewStock = Product::where('id',$orderItem->product_id)->first()->stock + $orderItem->quantity;
                Product::where('id',$orderItem->product_id)->update(['stock' => $productNewStock]);
                OrderItem::where('product_id',$orderItem->product_id)->delete();

            }
            Order::where('order_id',$orderId)->delete();
            DiscountDetail::where('order_id',$orderId)->delete();

            $successes[] = array(
                'order_id' => $orderId,
                'message' => "Order deleted successfully."
            );
        }

        return response()->json([
            'result' => array(
                'errors' => [],
                'successes' => $successes
            )
        ],200);


    }

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
            'orders.*.items.*.quantity' => 'quantity'
        ];

        $validateRequest = Validator::make($request->all(),[
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|unique:orders,order_id|distinct',
            'orders.*.customer_id' => 'required|integer|exists:customers,id',
            'orders.*.items' => 'required|array|min:0',
            'orders.*.items.*.product_id' => 'required|integer|exists:products,id',
            'orders.*.items.*.quantity' => 'required|integer'
        ],[

            'orders.*.items.*.product_id.exists' => array('field' => ':attribute','message' => 'This product not found in databases.'),
            'orders.*.customer_id.exists' => array('field' => ':attribute','message' => 'This customer not found in databases.'),
            'orders.*.id.unique' => array('field' => ':attribute','message' => 'This order already saved, please delete before saving.'),
            'orders.*.id.distinct' => array('field' => ':attribute','message' => 'You are send same order_id values in one request, please check.'),

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
                    $orderProductUnitPrice = Product::where('id',$item['product_id'])->first()->price;
                    $productStock = Product::where('id',$item['product_id'])->first()->stock;
                    $productCategory = Product::where('id',$item['product_id'])->first()->category;

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
                        $order['items'][$itemKey]['unit_price'] = $orderProductUnitPrice;

                    }

                }

                if($orderAvailability === 1){

                    DBController::clearOldData($orderId);

                    $totalDiscountPrice = $this->calculateOrderDiscounts($orderId,$order['items']);

                    $saveOrderArr = new Order([
                        'order_id' => $orderId,
                        'customer_id' => $orderCustomerId,
                        'total_price' => $calculatedTotalPrice,
                        'discounted_price' => $totalDiscountPrice
                    ]);

                    $saveOrderArr->save();
                    $userCurrentRevenue = Customer::where('id',$orderCustomerId)->first()->revenue;
                    $userNewRevenue = $userCurrentRevenue + $totalDiscountPrice;
                    Customer::where('id',$orderCustomerId)->update(['revenue' => $userNewRevenue]);

                    $successes[] = array(
                        'order_id' => $orderId,
                        'message' => "Order saved successfully."
                    );

                    foreach ($order['items'] as $item) {

                        $orderItemsSaveArr = new OrderItem([
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_unit_price'],
                            'order_id' => $orderId,
                        ]);
                        $orderItemsSaveArr->save();

                        $productStock = Product::where('id',$item['product_id'])->first()->stock;
                        $remainingStock = $productStock - $item['quantity'];
                        Product::where('id',$item['product_id'])->update(['stock' => $remainingStock]);

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
        $discountIds = Discount::select('discount_id')->distinct()->get();

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
