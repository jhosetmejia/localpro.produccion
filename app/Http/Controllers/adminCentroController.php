<?php

namespace App\Http\Controllers;

use App\Support\CentroControl;
use Illuminate\Http\Request;

/** 2026-11-25 (Jhoset): Centro de control en vivo de LocalPro + PFN + NES (solo súper administrador). */
class adminCentroController extends Controller
{
    public function index()
    {
        return view('admin.centro', ['sistemas' => ['localpro' => ['nombre' => 'LocalPro', 'url' => url('/'), 'color' => '#22c55e']] + CentroControl::SISTEMAS]);
    }

    public function datos(Request $request)
    {
        return response()->json(CentroControl::todo($request->boolean('fresco')), 200, ['Cache-Control' => 'no-store']);
    }
}
