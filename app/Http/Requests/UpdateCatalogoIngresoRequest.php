<?php

namespace App\Http\Requests;

use App\Models\CatalogoIngreso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogoIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogos.gestionar');
    }

    public function rules(): array
    {
        $cuenta = $this->route('catalogo_ingreso') ?? $this->route('ingreso');
        $cuentaId = $cuenta instanceof CatalogoIngreso ? $cuenta->id : $cuenta;

        return [
            'codigo' => [
                'required',
                'string',
                'min:3',
                'max:10',
                'alpha_num',
                Rule::unique('catalogo_ingresos', 'codigo')->ignore($cuentaId),
            ],
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
