<?php

namespace App\Http\Requests;

use App\Models\Caja;
use App\Models\CatalogoIngreso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ingresos.crear');
    }

    public function rules(): array
    {
        return [
            'caja_id' => ['required', 'integer', 'exists:cajas,id'],
            'fecha' => ['required', 'date'],
            'cuenta_ingreso_id' => ['required', 'integer', 'exists:catalogo_ingresos,id'],
            'monto' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'recibo' => ['nullable', 'string', 'max:100'],
            'aportante_id' => ['nullable', 'integer', 'exists:aportantes,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            $cajaId = $this->input('caja_id');

            if ($cajaId) {
                $caja = Caja::find($cajaId);
                if ($caja) {
                    if (! $caja->activa) {
                        $v->errors()->add('caja_id', 'La caja seleccionada está inactiva y no admite operaciones contables.');
                    }

                    if (! $user->can('cajas.gestionar') && ! $user->cajas()->where('cajas.id', $caja->id)->exists()) {
                        $v->errors()->add('caja_id', 'No tiene permisos para operar en la caja seleccionada.');
                    }
                }
            }

            $cuentaId = $this->input('cuenta_ingreso_id');
            if ($cuentaId) {
                $cuenta = CatalogoIngreso::find($cuentaId);
                if ($cuenta && ! $cuenta->activo) {
                    $v->errors()->add('cuenta_ingreso_id', 'La cuenta de ingreso seleccionada está inactiva.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'caja_id.required' => 'Debe seleccionar una caja contable.',
            'cuenta_ingreso_id.required' => 'Debe seleccionar una cuenta de ingreso.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'monto.decimal' => 'El monto no puede tener más de 2 decimales.',
        ];
    }
}
