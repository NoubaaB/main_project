<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\CustomChart;
use App\Models\StaticCharge;
use App\Models\User;
use App\Models\WarehouseCategory;
use App\Models\Origin;
use App\Models\Fishery;
use App\Models\PackageType;
use App\Models\FishType;
use App\Models\FishProcessType;
use App\Models\ChargeCategory;
use App\Models\ShippingCategory;
use App\Models\Client;
use App\Models\FishFamily;
use App\Models\Formula;
use App\Models\GuanoType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\Ship;
use App\Models\Supplier;
use App\Models\Truck;
use App\Models\Warehouse;
use App\Models\DetailChargeCategory;
use App\Models\DetailShippingCategory;
use App\Models\DetailProductionCategory;
use App\Models\DetailInventoryCategory;
use App\Models\WarehouseTransferCategory;
use App\Models\ProductionCategory;
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

        // Check if user account is disabled
        if (!$user->is_active) {
            return response()->json([
                'message' => 'account_disabled',
                'disabled' => true,
            ], 403);
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
        /** @var User $auth_user */
        $auth_user = Auth::user();

        $rules = [
            'name' => 'required|string|max:50',
            'email' => "required|email|max:100|unique:users,email,{$auth_user->id},id",
        ];

        // Password is optional – only validate if provided
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6|max:250|confirmed';
        }

        $data = $request->validate($rules);

        // Only hash password if it was provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $auth_user->update($data);
        $auth_user->load('role');

        return response()->json(['user' => $auth_user], 200);
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
        $ships = Ship::all();
        $fish_types = FishType::all();
        $fish_families = FishFamily::all();
        $package_types = PackageType::all();
        $guano_types = GuanoType::all();
        $warehouses = Warehouse::with('category')->get();
        $warehouse_categories = WarehouseCategory::all();
        $fisheries = Fishery::all();
        $origins = Origin::all();
        $charge_categories = ChargeCategory::withCount('charges')->get();
        $shipping_categories = ShippingCategory::withCount('shippings')->get();
        $production_categories = ProductionCategory::withCount('productions')->get();
        $detail_charge_categories = DetailChargeCategory::all();
        $detail_shipping_categories = DetailShippingCategory::all();
        $detail_production_categories = DetailProductionCategory::all();
        $detail_inventory_categories = DetailInventoryCategory::all();
        $warehouse_transfer_categories = WarehouseTransferCategory::all();
        $clients = Client::all();
        $formulas = Formula::all();
        $trucks = Truck::all();
        $fish_process_types = FishProcessType::all();
        $roles = \App\Models\Role::withCount('users')->get();

        // Company settings as key-value with logo URLs resolved
        $company_settings = CompanySetting::getAllSettings();
        if (!empty($company_settings['logo_invoice_path'])) {
            $company_settings['logo_invoice_url'] = asset('storage/' . $company_settings['logo_invoice_path']);
        }
        if (!empty($company_settings['logo_invoice_body_path'])) {
            $company_settings['logo_invoice_body_url'] = asset('storage/' . $company_settings['logo_invoice_body_path']);
        }

        return [
            "suppliers" => $suppliers,
            "ships" => $ships,
            "fish_types" => $fish_types,
            "fish_families" => $fish_families,
            "fish_process_types" => $fish_process_types,
            "package_types" => $package_types,
            "guano_types" => $guano_types,
            "warehouses" => $warehouses,
            "warehouse_categories" => $warehouse_categories,
            "charge_categories" => $charge_categories,
            "shipping_categories" => $shipping_categories,
            "clients" => $clients,
            "fisheries" => $fisheries,
            "origins" => $origins,
            "formulas" => $formulas,
            "trucks" => $trucks,
            "production_categories" => $production_categories,
            "detail_charge_categories" => $detail_charge_categories,
            "detail_shipping_categories" => $detail_shipping_categories,
            "detail_production_categories" => $detail_production_categories,
            "detail_inventory_categories" => $detail_inventory_categories,
            "warehouse_transfer_categories" => $warehouse_transfer_categories,
            "roles" => $roles,
            "company_settings" => $company_settings,
            "custom_charts" => CustomChart::with('series')->where('is_active', true)->orderBy('display_order')->get(),
            "static_charges" => StaticCharge::orderBy('created_at', 'desc')->get(),
        ];
    }
}
