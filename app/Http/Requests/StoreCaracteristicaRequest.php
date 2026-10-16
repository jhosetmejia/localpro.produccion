<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaracteristicaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    // Categorías: nombre parejo ("CHAQUETA NACIONAL" → "Chaqueta nacional")
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('categorias.*') && $this->filled('nombre')) {
            $this->merge(['nombre' => \App\Support\Textos::nombre($this->input('nombre'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'max:60', \Illuminate\Validation\Rule::unique('caracteristicas', 'nombre')->where('tienda_id', \App\Support\TiendaActual::id())],
            'descripcion' => 'nullable|max:255'
        ];
    }
}
