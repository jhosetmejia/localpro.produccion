<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonaRequest extends FormRequest
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
            'razon_social' => 'required|max:80',
            'direccion' => 'required|max:80',
            'tipo_persona' => 'required|string',
            'documento_id' => 'required|integer|exists:documentos,id',
            'telefono' => ['nullable', 'string', 'max:20', \App\Support\Validar::celular()],
            'email' => 'nullable|email|max:120',
            'numero_documento' => ['required', 'max:20', \App\Support\Validar::documento(\App\Models\Documento::find(request('documento_id'))?->tipo_documento), \Illuminate\Validation\Rule::unique('personas', 'numero_documento')->where('tienda_id', \App\Support\TiendaActual::id())]
        ];
    }
}
