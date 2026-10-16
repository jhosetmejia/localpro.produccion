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
            'img_path' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'precio_venta' => 'required|numeric|min:0|max:999999999',
            'impuesto' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Dian\Catalogos::IMPUESTOS))],
            'precio_costo' => 'nullable|numeric|min:0|max:999999999',
            'controla_inventario' => 'nullable|boolean',
            'marca_id' => 'nullable|integer|exists:marcas,id',
            'presentacione_id' => 'nullable|integer|exists:presentaciones,id',
            'precio_antes' => 'nullable|numeric|min:0|max:999999999',
            'foto_color' => 'nullable|array|max:40',
            'foto_color.*' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'para' => ['nullable', \Illuminate\Validation\Rule::in([...array_keys(\App\Models\Producto::PARA), 'todos'])],
            'categorias' => 'nullable|array'
        ];
    }

    public function attributes()
    {
        return [
            'img_path' => 'foto',
            'marca_id' => 'marca',
            'presentacione_id' => 'presentación'
        ];
    }

    public function messages()
    {
        return [
            'img_path.image' => 'Esa foto no se pudo leer. Súbala en JPG, PNG o WebP (o tómele un pantallazo y suba el pantallazo).',
            'img_path.mimes' => 'Esa foto no se pudo leer. Súbala en JPG, PNG o WebP (o tómele un pantallazo y suba el pantallazo).',
            'img_path.max' => 'La foto pesa mucho (máximo 4 MB).',
            'foto_color.*.image' => 'Una foto de color no se pudo leer. Súbala en JPG, PNG o WebP.',
            'foto_color.*.mimes' => 'Una foto de color no se pudo leer. Súbala en JPG, PNG o WebP.',
            'foto_color.*.max' => 'Una foto de color pesa mucho (máximo 4 MB).',
            'codigo.required' => 'Se necesita un campo código'
        ];
    }
}
