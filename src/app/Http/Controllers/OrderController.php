<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public $successStatus = 200;

    public function addOrder(Request $request)
    {

        $validateRequest = Validator::make($request->all(),[
            '*.id' => 'required|integer|unique:orders',
            '*.customer_id' => 'required|integer|',
            '*.items' => 'required|array|min:0',
            '*.items.*.product_id' => 'required|integer|exists:products,id',
            '*.items.*.quantity' => 'required|integer|',
            '*.items.*.unit_price' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/|'
        ]);

        return response()->json([
            'errors' => $validateRequest->errors()
        ]);

    }

}
