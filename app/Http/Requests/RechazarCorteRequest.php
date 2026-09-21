<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarCorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cortes.aprobar');
    }

    public function rules(): array
    {
        return [
            'observaciones' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observaciones.required' => 'Debe ingresar una observación explicando el motivo del rechazo.',
            'observaciones.min' => 'La observación de rechazo debe contener al menos 10 caracteres explicativos.',
        ];
    }
}
