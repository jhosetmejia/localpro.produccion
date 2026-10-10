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
            'img_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'precio_venta' => 'required|numeric|min:0|max:999999999',
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
