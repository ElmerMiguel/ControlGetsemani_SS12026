<?php

namespace App\Http\Requests;

use App\Models\CatalogoEgreso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogoEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogos.gestionar');
    }

    public function rules(): array
    {
        $cuenta = $this->route('catalogo_egreso') ?? $this->route('egreso');
        $cuentaId = $cuenta instanceof CatalogoEgreso ? $cuenta->id : $cuenta;

        return [
            'codigo' => [
                'required',
                'string',
                'min:3',
                'max:10',
                'alpha_num',
                Rule::unique('catalogo_egresos', 'codigo')->ignore($cuentaId),
            ],
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
