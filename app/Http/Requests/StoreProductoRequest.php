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
            'categorias' => 'nullable|array',
            'con_tallas' => 'nullable|boolean',
            'variantes' => 'nullable|array|max:300',
            'variantes.*.talla' => 'nullable|string|max:40',
            'variantes.*.color' => 'nullable|string|max:60',
            'variantes.*.cantidad' => 'nullable|integer|min:0|max:100000',
        ];
    }

    public function attributes()
    {
        return [
            'marca_id' => 'marca',
            'presentacione_id' => 'presentación',
            'variantes.*.talla' => 'talla',
            'variantes.*.color' => 'color',
            'variantes.*.cantidad' => 'cantidad',
        ];
    }

    public function messages()
    {
        return [
            'codigo.required' => 'Se necesita un campo código',
            'nombre.unique' => 'Ya tiene un producto con ese nombre.',
            'variantes.*.talla.max' => 'Una talla es muy larga (máximo 40 letras).',
            'variantes.*.color.max' => 'Un color es muy largo (máximo 60 letras).',
            'variantes.max' => 'Son demasiadas combinaciones de talla y color.'
        ];
    }
}
