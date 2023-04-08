<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\StatefulGuard;

class LogoutController extends Controller
{
    public function logout(Request $request, StatefulGuard $guard)
    {
        $guard->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
