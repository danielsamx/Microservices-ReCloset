<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    // RF-01 Registration
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed cast
        ]);
        $token = $user->createToken('web')->plainTextToken;
        Log::info('auth.register', ['user_id' => $user->id]);
        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    // RF-02 Login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::warning('auth.login_failed', ['email_hash' => sha1(strtolower($data['email']))]);
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }
        $token = $user->createToken('web')->plainTextToken;
        Log::info('auth.login', ['user_id' => $user->id]);
        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    // Internal introspection endpoint used by other microservices
    public function verify(Request $request)
    {
        $u = $request->user();
        return response()->json(['user' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]]);
    }
}
