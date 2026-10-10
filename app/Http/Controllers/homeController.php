<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class homeController extends Controller
{
    public function index(){
        if(!Auth::check()){
            return view('welcome');
        }
        // Cuenta registrada desde la web, aún sin rol asignado
        if(Auth::user()->roles()->count() === 0){
            return view('panel.pendiente');
        }
        return view('panel.index');
    }

}
