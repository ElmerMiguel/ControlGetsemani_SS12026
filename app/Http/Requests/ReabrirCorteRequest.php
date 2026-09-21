<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReabrirCorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cortes.reabrir');
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
            'observaciones.required' => 'Debe ingresar el motivo de reapertura del corte.',
            'observaciones.min' => 'El motivo de reapertura debe contener al menos 10 caracteres explicativos.',
        ];
    }
}
