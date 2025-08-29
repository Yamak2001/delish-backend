<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleAuthController extends Controller
{
    /**
     * Simple login to access Telescope
     */
    public function simpleLogin(Request $request)
    {
        $email = $request->query('email');
        $password = $request->query('password');
        
        if ($email === 'ayamak@wascash.com' && $password === 'Delish2025!') {
            // Find and login the user
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                Auth::login($user);
                $request->session()->regenerate();
                return redirect('https://api.delishonline.shop/telescope');
            }
        }
        
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}