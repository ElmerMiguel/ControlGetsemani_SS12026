<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La política específica del modelo se evalúa en el controlador
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de anulación es obligatorio.',
            'motivo.min' => 'El motivo de anulación debe contener al menos 10 caracteres explicativos.',
            'motivo.max' => 'El motivo de anulación no puede exceder los 255 caracteres.',
        ];
    }
}
