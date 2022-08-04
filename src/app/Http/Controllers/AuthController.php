<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public $successStatus = 200;

    /**
     * Create User
     *
     * @param [string] name
     * @param [string] email
     * @param [string] password
     * @return [string] message
     */

    public function register(Request $request)
    {

        $validateRequest = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);

        if($validateRequest->passes()){

            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            $user->save();

            $message['success'] = 'User created successfully';

            return response()->json([
                'message' => $message
            ], 201);

        }else{

            return response()->json([
                'errors' => $validateRequest->errors()
            ]);

        }



    }

    /**
     * Kullanıcı Girişi ve token oluşturma
     *
     * @param [string] email
     * @param [string] password
     * @return [string] token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [string] success
     */

    public function login(Request $request)
    {

        $validateRequest = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if($validateRequest->passes()){

            $credentials = request(['email','password']);

            if(Auth::attempt($credentials)){
                $user = Auth::user();
                $message['token'] = $user->createToken('orderCase1')->accessToken;
                $message['token_type'] = 'Bearer';
                $message['expires_at'] = Carbon::parse(Carbon::now()->addDays(1))->toDateTimeString();
                $message['success'] = 'User Login Successful';

                return response()->json(
                    ['message' => $message],
                    $this->successStatus
                );
            }else{
                return response()->json(
                    ['error' => 'Unauthorized'],
                    401
                );
            }

        }else{

            return response()->json([
                'errors' => $validateRequest->errors()
            ]);

        }

    }

}
