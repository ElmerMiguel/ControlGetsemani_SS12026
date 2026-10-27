<?php

namespace App\Http\Requests\Inventario;

use App\Enums\EstadoBien;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventario.crear');
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'codigo' => ['required', 'string', 'max:50', 'unique:inventarios,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'estado' => ['required', new Enum(EstadoBien::class)],
            'fecha_registro' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.required' => 'Debe seleccionar un departamento.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido.',
            'codigo.required' => 'El código del bien es obligatorio.',
            'codigo.unique' => 'Ya existe un bien registrado con este código institucional.',
            'nombre.required' => 'El nombre del bien es obligatorio.',
            'fecha_registro.before_or_equal' => 'La fecha de registro no puede ser futura.',
        ];
    }
}
