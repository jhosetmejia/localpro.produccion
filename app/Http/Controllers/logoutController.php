<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class logoutController extends Controller
{
    public function logout(Request $request){
        Auth::logout();
        // Sesión nueva con token nuevo: así el formulario de ingreso que se abre después sí funciona (no da 419)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
