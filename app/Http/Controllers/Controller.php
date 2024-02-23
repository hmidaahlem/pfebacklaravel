<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin;
use App\Models\Sadmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationMail;




class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function signup(Request $request)
{
    $data = $request->validate([
        'nom' => 'required',
        'prenom' => 'required',
        'login' => 'required',
        'password' => 'required|min:8',
        'email' => 'required|email',
    ]);
    $existingUser = User::where('email', $data['email'])
    ->orWhere('login', $data['login'])
    ->first();

if ($existingUser) {
    return response()->json(['message' => 'User already exists'], 422);
}
    // Vérifier si l'email ou le login est déjà utilisé dans les autres rôles
    if (Admin::where('email', $data['email'])->orWhere('login', $data['login'])->exists() ||
        Sadmin::where('email', $data['email'])->orWhere('login', $data['login'])->exists()) {
        return response()->json(['message' => 'Email or login already exists in other roles'], 422);
    }

    $password = $data['password'];

    $data['password'] = bcrypt($data['password']);

    $user = User::create($data);
    Mail::to($user->email)->send(new UserRegistrationMail($user, $password));

    return response()->json(['message' => 'Signup successful', 'user' => $user], 201);
}
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        if (Auth::guard('user')->attempt($credentials)) {
            $user = Auth::guard('user')->user();

            return response()->json(['user' => $user], 200);
        } else {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }
    }
}