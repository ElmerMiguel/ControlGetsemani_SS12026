<?php

namespace App\Http\Requests;

use App\Models\Caja;
use Illuminate\Foundation\Http\FormRequest;

class SolicitarCorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cortes.solicitar');
    }

    public function rules(): array
    {
        return [
            'caja_id' => ['required', 'exists:cajas,id'],
            'periodo_fin' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $cajaId = $this->input('caja_id');

            if ($cajaId && ! $user->hasRole('admin')) {
                $tieneAcceso = $user->cajas()->where('cajas.id', $cajaId)->exists();
                if (! $tieneAcceso) {
                    $validator->errors()->add('caja_id', 'No tiene permisos para solicitar cortes en la caja especificada.');
                }
            }

            if ($cajaId) {
                $caja = Caja::find($cajaId);
                if ($caja && ! $caja->activa) {
                    $validator->errors()->add('caja_id', 'No se pueden solicitar cortes en una caja inactiva.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'caja_id.required' => 'Debe seleccionar una caja para el corte.',
            'caja_id.exists' => 'La caja seleccionada no es válida.',
            'periodo_fin.required' => 'Debe indicar la fecha de fin del período de corte.',
            'periodo_fin.before_or_equal' => 'La fecha de fin del período no puede ser posterior al día de hoy.',
        ];
    }
}
