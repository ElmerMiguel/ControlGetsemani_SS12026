<?php

namespace App\Http\Requests;

use App\Enums\TipoDepartamento;
use App\Models\Departamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('departamentos.gestionar');
    }

    public function rules(): array
    {
        $depto = $this->route('departamento');
        $deptoId = $depto instanceof Departamento ? $depto->id : $depto;

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departamentos', 'nombre')->ignore($deptoId),
            ],
            'tipo' => ['required', new Enum(TipoDepartamento::class)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
