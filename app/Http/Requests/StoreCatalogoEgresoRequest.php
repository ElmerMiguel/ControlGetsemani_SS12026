<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogoEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogos.gestionar');
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'min:3', 'max:10', 'alpha_num', 'unique:catalogo_egresos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código ingresado ya está asignado a otra cuenta de egreso.',
            'codigo.alpha_num' => 'El código debe contener únicamente caracteres alfanuméricos.',
        ];
    }
}
