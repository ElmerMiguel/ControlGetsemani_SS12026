<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogoIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogos.gestionar');
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'min:3', 'max:10', 'alpha_num', 'unique:catalogo_ingresos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código ingresado ya está asignado a otra cuenta de ingreso.',
            'codigo.alpha_num' => 'El código debe contener únicamente caracteres alfanuméricos.',
        ];
    }
}
