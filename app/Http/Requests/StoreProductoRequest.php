<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'max:50', \Illuminate\Validation\Rule::unique('productos', 'codigo')->where('tienda_id', \App\Support\TiendaActual::id())],
            'nombre' => ['required', 'max:80', \Illuminate\Validation\Rule::unique('productos', 'nombre')->where('tienda_id', \App\Support\TiendaActual::id())],
            'descripcion' => 'nullable|max:255',
            'fecha_vencimiento' => 'nullable|date',
            'img_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'precio_venta' => 'required|numeric|min:0|max:999999999',
            'precio_costo' => 'nullable|numeric|min:0|max:999999999',
            'controla_inventario' => 'nullable|boolean',
            'cantidad_inicial' => 'nullable|integer|min:0|max:100000',
            'marca_id' => 'nullable|integer|exists:marcas,id',
            'presentacione_id' => 'nullable|integer|exists:presentaciones,id',
            'categorias' => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'marca_id' => 'marca',
            'presentacione_id' => 'presentación'
        ];
    }

    public function messages()
    {
        return [
            'codigo.required' => 'Se necesita un campo código'
        ];
    }
}
