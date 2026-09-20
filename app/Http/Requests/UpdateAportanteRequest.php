<?php

namespace App\Http\Requests;

use App\Models\Aportante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAportanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('aportantes.gestionar');
    }

    public function rules(): array
    {
        $aportante = $this->route('aportante');
        $aportanteId = $aportante instanceof Aportante ? $aportante->id : $aportante;

        return [
            'nombre_completo' => ['required', 'string', 'max:150'],
            'cui_dpi' => [
                'nullable',
                'string',
                'digits:13',
                Rule::unique('aportantes', 'cui_dpi')->ignore($aportanteId),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'cui_dpi.digits' => 'El CUI/DPI debe contener exactamente 13 dígitos numéricos.',
            'cui_dpi.unique' => 'El CUI/DPI ingresado ya pertenece a otro aportante registrado.',
        ];
    }
}
