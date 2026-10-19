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
            'img_path' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'precio_venta' => 'required|numeric|min:0|max:999999999',
            'impuesto' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Dian\Catalogos::IMPUESTOS))],
            'precio_costo' => 'nullable|numeric|min:0|max:999999999',
            'controla_inventario' => 'nullable|boolean',
            'cantidad_inicial' => 'nullable|numeric|min:0|max:100000',
            'unidad' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Producto::UNIDADES))],
            'marca_id' => 'nullable|integer|exists:marcas,id',
            'presentacione_id' => 'nullable|integer|exists:presentaciones,id',
            'categorias' => 'nullable|array',
            'precio_antes' => 'nullable|numeric|min:0|max:999999999',
            'para' => ['nullable', \Illuminate\Validation\Rule::in([...array_keys(\App\Models\Producto::PARA), 'todos'])],
            'con_tallas' => 'nullable|boolean',
            'variantes' => 'nullable|array|max:300',
            'variantes.*.talla' => 'nullable|string|max:40',
            'variantes.*.color' => 'nullable|string|max:60',
            'variantes.*.cantidad' => 'nullable|integer|min:0|max:100000',
            'variantes.*.barras' => 'nullable|string|max:1000',
            'codigo_barras' => 'nullable|string|max:1000',
        ];
    }

    public function attributes()
    {
        return [
            'img_path' => 'foto',
            'marca_id' => 'marca',
            'presentacione_id' => 'presentación',
            'variantes.*.talla' => 'talla',
            'variantes.*.color' => 'color',
            'variantes.*.cantidad' => 'cantidad',
            'variantes.*.barras' => 'código de barras',
            'codigo_barras' => 'código de barras',
        ];
    }

    public function messages()
    {
        return [
            'img_path.image' => 'Esa foto no se pudo leer. Súbala en JPG, PNG o WebP (o tómele un pantallazo y suba el pantallazo).',
            'img_path.mimes' => 'Esa foto no se pudo leer. Súbala en JPG, PNG o WebP (o tómele un pantallazo y suba el pantallazo).',
            'img_path.max' => 'La foto pesa mucho (máximo 4 MB).',
            'codigo.required' => 'Se necesita un campo código',
            'nombre.unique' => 'Ya tiene un producto con ese nombre.',
            'variantes.*.talla.max' => 'Una talla es muy larga (máximo 40 letras).',
            'variantes.*.color.max' => 'Un color es muy largo (máximo 60 letras).',
            'variantes.max' => 'Son demasiadas combinaciones de talla y color.'
        ];
    }
}
