<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|string',
                'name' => 'required|string|unique:users,name',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => "Registration process failed",
                    'status' => 422,
                    'details' => $validator->messages()
                ]
            ], 422);
        }

        $user = User::create([
            'email' => $request->get('email'),
            'name' => $request->get('name'),
            'password' => Hash::make($request->get('password')),
            'balance' => 10000
        ]);

        $user->assignRole('user');

        return response()->json([
            'success' => true,
            'message' => 'User has been registered successfully'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required|min:8|string',
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'error' => [
                        'message' => 'Login process failed.',
                        'status' => 422,
                        'details' => $validator->messages()
                    ]
                    ], 422
            );
        }
        $user = User::select('id','email', 'password')
            ->where('email', '=', $request->get('email'))
            ->first();

        $password = $request->get('password');
        if (!$user || !Hash::check($password, $user->password))
        {
            return response()->json(
                [
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid credientals',
                        'status' => 401,
                    ]
                    ], 401
            );
        }
        
        $token = $user->createToken('token', ['*'], Carbon::now()->addHour());
        return response()->json([
            'success' => true,
            'message' => 'You have been successfully logged in',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'expires_at' => Carbon::parse($token->accessToken->expires_at)->toDateTimeString()
            ]
        ], 200);
    }
}
