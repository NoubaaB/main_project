<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Origin;
use App\Models\Fishery;
use App\Models\Package;
use App\Models\FishType;
use App\Models\ChargeType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class AuthController extends Controller implements HasMiddleware
{
    //

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['register', 'login']),
        ];
    }


    public function register(Request $request): JsonResponse
    {
        //validate data
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users,email|max:100|min:6',
            'phone' => 'sometimes|string|max:25',
            'address' => 'sometimes|string|max:250',
            'password' => 'min:6|required_with:confirm_password|same:confirm_password|max:250',
            'role_id' => 'required|numeric|distinct|exists:roles,id'
        ]);


        //hash password
        $data['password'] = Hash::make($data['password']);

        //create user
        $user = User::create($data);

        //create token
        $token = $user->createToken($user->name)->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
            ...$this->getData()
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        //validate data
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where("email", $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => "email or password not correct"]);
        }

        //create token
        $token = $user->createToken($user->name)->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
            ...$this->getData()
        ], 201);
    }

    public function logout(): JsonResponse
    {
        //delete all tokens

        /** @var User $auth_user */
        $auth_user = Auth::user();

        $status = $auth_user->tokens()->delete();

        return response()->json(["status" => $status], 200);
    }

    public function me(): JsonResponse
    {
        $user = Auth::user();
        // $this->authorize("view", $user);

        return response()->json([
            "user" => $user,
            ...$this->getData()
        ], 200);
    }

    public function update(Request $request): JsonResponse
    {
        //get auth user

        /** @var User $auth_user */
        $auth_user = Auth::user();

        //validate data
        $data = $request->validate([
            'name' => 'required|string|max:50',
            "email" => "required|unique:users,email,{$auth_user->id},id",
            'role_id' => 'required|numeric|distinct|exists:roles,id',
            'password' => 'min:6|required_with:confirm_password|same:confirm_password|max:250'
        ]);

        //hash password
        $data['password'] = Hash::make($data['password']);
        //update user
        $auth_user->update($data);

        return response()->json(["user" => $auth_user], 200);
    }

    public function forget(Request $request): JsonResponse
    {
        //validate data
        $data = $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        //find user by email
        $user = User::where('email', $data['email'])->first();
        if (!isset($user->id)) {
            return response()->json(['error' => "user with that email is not found"], 401);
        }

        //create token
        $token = Str::random(32);

        //create reset_password
        $passwordReset = PasswordReset::create([
            "email" => $user->email,
            "token" => $token,
        ]);

        //send email to user email
        // Mail::to($user)->send(new ResetPasswordMail($token));

        return response()->json(["status" => true], 200);
    }

    public function reset(Request $request)
    {
        //validate data
        $data = $request->validate([
            'token' => 'required|string|exists:password_resets,token',
            'email' => 'required|string|email|exists:password_resets,email',
            'password' => 'min:6|required_with:confirm_password|same:confirm_password|max:250',
        ]);

        //find password_reset by token
        $passwordReset = PasswordReset::where("token", $data["token"])->first();
        if (!isset($passwordReset->email)) {
            return response()->json(['error' => "Invalid token"], 401);
        }

        //find user by email
        $user = User::where('email', $passwordReset->email)->first();

        //hash password
        $password = Hash::make($data["password"]);

        //update user password
        $status = $user->update(['password' => $password]);

        //delete all token that associated with this email
        PasswordReset::where('email', $passwordReset->email)->delete();

        // dd($user, $password);
        return response()->json(['message' => $status], 200);
    }

    function getData(): array
    {
        $suppliers = Supplier::all();
        $fish_types = FishType::all();
        $packages = Package::all();
        $stocks = Stock::all();
        $fisheries = Fishery::all();
        $origins = Origin::all();
        $charge_types = ChargeType::all();
        return [
            "suppliers" => $suppliers,
            "fish_types" => $fish_types,
            "packages" => $packages,
            "stocks" => $stocks,
            "charge_types" => $charge_types,
            "fisheries" => $fisheries,
            "origins" => $origins,
        ];
    }
}
