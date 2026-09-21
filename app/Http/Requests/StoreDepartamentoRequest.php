<?php

namespace App\Http\Requests;

use App\Enums\TipoDepartamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('departamentos.gestionar');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:departamentos,nombre'],
            'tipo' => ['required', new Enum(TipoDepartamento::class)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
