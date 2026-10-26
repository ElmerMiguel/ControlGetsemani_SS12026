<?php

namespace App\Http\Requests\Inventario;

use App\Enums\EstadoBien;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CambiarEstadoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cambiarEstado', $this->route('inventario'));
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', new Enum(EstadoBien::class)],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar un nuevo estado.',
            'motivo.required' => 'Debe especificar el motivo del cambio de estado para la auditoría.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
