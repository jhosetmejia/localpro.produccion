<?php

namespace App\Http\Controllers;

use App\Support\AnunciosCut;
use Illuminate\Http\Request;

/** Súper administrador: anuncios que se ven en LocalProCut (panel de calidad y mientras exporta) */
class adminCutAnunciosController extends Controller
{
    public function guardar(Request $request)
    {
        if ($request->boolean('restaurar')) {
            AnunciosCut::restaurar();
            return back()->with('success', 'Anuncios automáticos restaurados (eventos próximos, Abril Store y LocalPro).');
        }
        $d = $request->validate([
            'a' => 'nullable|array|max:20',
            'a.*.t' => 'nullable|string|max:90',
            'a.*.s' => 'nullable|string|max:120',
            'a.*.u' => 'nullable|string|max:400',
            'a.*.e' => 'nullable|string|max:8',
            'a.*.img' => 'nullable|string|max:400',
            'a.*.hasta' => 'nullable|date',
            'a.*.on' => 'nullable|boolean',
            'a.*.d' => 'nullable|in:moda,pelea',
            'a.*.fecha' => 'nullable|string|max:60',
        ], ['a.*.hasta.date' => 'La fecha "hasta" no es válida.', 'a.*.t.max' => 'El título es muy largo (máximo 90 letras).']);
        $lista = [];
        foreach ($d['a'] ?? [] as $a) {
            $t = trim($a['t'] ?? ''); $u = trim($a['u'] ?? '');
            if ($t === '' && $u === '') continue;
            if ($u !== '' && !preg_match('~^(https?://|/)~i', $u)) $u = 'https://' . $u;
            $img = trim($a['img'] ?? '');
            $lista[] = ['e' => trim($a['e'] ?? '') ?: '📢', 't' => $t, 's' => trim($a['s'] ?? ''), 'u' => $u,
                'img' => preg_match('~^(https?://|/)~i', $img) ? $img : null, 'hasta' => $a['hasta'] ?? null, 'on' => !empty($a['on']), 'd' => $a['d'] ?? null, 'fecha' => trim($a['fecha'] ?? '') ?: null];
        }
        AnunciosCut::guardar($lista);
        return back()->with('success', 'Anuncios de LocalProCut guardados.');
    }
}
