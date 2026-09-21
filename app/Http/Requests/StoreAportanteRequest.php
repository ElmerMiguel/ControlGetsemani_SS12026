<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAportanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('aportantes.gestionar');
    }

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:150'],
            'cui_dpi' => ['nullable', 'string', 'digits:13', 'unique:aportantes,cui_dpi'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'cui_dpi.digits' => 'El CUI/DPI debe contener exactamente 13 dígitos numéricos.',
            'cui_dpi.unique' => 'El CUI/DPI ingresado ya pertenece a otro aportante registrado.',
        ];
    }
}
