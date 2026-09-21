<?php

namespace App\Http\Requests;

use App\Enums\MedioCaja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cajas.gestionar');
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cajas', 'nombre')->where('departamento_id', $this->input('departamento_id')),
            ],
            'codigo' => ['required', 'string', 'max:20', 'unique:cajas,codigo'],
            'medio' => ['required', new Enum(MedioCaja::class)],
            'saldo_apertura' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'fecha_apertura' => ['required', 'date'],
            'activa' => ['sometimes', 'boolean'],
            'tesoreros' => ['nullable', 'array'],
            'tesoreros.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una caja con este nombre en el departamento seleccionado.',
            'codigo.unique' => 'El código asignado ya pertenece a otra caja registrada.',
            'saldo_apertura.decimal' => 'El saldo de apertura debe tener un máximo de 2 decimales.',
        ];
    }
}
