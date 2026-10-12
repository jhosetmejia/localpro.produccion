<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
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
        $producto = $this->route('producto');
        return [
            'codigo' => ['required', 'max:50', \Illuminate\Validation\Rule::unique('productos', 'codigo')->where('tienda_id', \App\Support\TiendaActual::id())->ignore($producto->id)],
            'nombre' => ['required', 'max:80', \Illuminate\Validation\Rule::unique('productos', 'nombre')->where('tienda_id', \App\Support\TiendaActual::id())->ignore($producto->id)],
            'descripcion' => 'nullable|max:255',
            'fecha_vencimiento' => 'nullable|date',
            'unidad' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Producto::UNIDADES))],
            'img_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'precio_venta' => 'required|numeric|min:0|max:999999999',
            'impuesto' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Dian\Catalogos::IMPUESTOS))],
            'precio_costo' => 'nullable|numeric|min:0|max:999999999',
            'controla_inventario' => 'nullable|boolean',
            'marca_id' => 'nullable|integer|exists:marcas,id',
            'presentacione_id' => 'nullable|integer|exists:presentaciones,id',
            'categorias' => 'nullable|array'
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
