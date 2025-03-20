<?php


namespace App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V2\CartItemsController;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\User;
use App\Notifications\LoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public static function validToken($bearerToken): bool
    {
        if (!$bearerToken) {
            return false;
        }

        try {
            $token = PersonalAccessToken::findToken($bearerToken);

            if (!$token) {
                return false;
            }

            $expiration = config('sanctum.expiration');
            if ($expiration && $token->created_at->lte(now()->subMinutes($expiration))) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function register(Request $request)
    {

        try {

        
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        $fields['password'] = Hash::make($fields['password']);


        $user = User::create($fields);

        if ($user->id == 1) {
            $user->assignRole('super_admin');
        } else {
            $user->assignRole('client');
        }

        $token = $user->createToken('register token')->plainTextToken;

        // merge The cart items
        if (isset($request->session_id)) {
            // dd($request->session_id);
            $sessionId = $request->session_id;
            CartItemsController::mergeCartItems($sessionId, $user->id);
        }


        return response()->json([
            'user' => $user,
            'role' => $user->getRoleNames(),
            'token' => $token,
            'message' => 'User registred successfully'
        ], 201);     }

        catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration',
                'status' => 'error' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users',
            'password' =>'required'

        ]);

        $user = User::where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'message' => 'Invalid credentials',
                'status' => 'error'
            ], 401);
        }


        $user->tokens()->delete();

        $token = $user->createToken('login token')->plainTextToken;


        if (isset($request->session_id)) {
            $sessionId = $request->session_id;
            CartItemsController::mergeCartItems($sessionId, $user->id);
        }

        return response()->json([
            "user" => $user,
            "token" =>  $token,
            'message' => 'Logged in successfully'
        ], 200);
    }

    public function logout(Request $request)
    {
        try {

            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Successfully logged out',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during logout',
                'status' => 'error'
            ], 500);
        }
    }
}
