<?php

namespace App\Http\Requests;

use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\Egreso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $egreso = $this->route('egreso');
        $egresoModel = $egreso instanceof Egreso ? $egreso : Egreso::find($egreso);

        return $egresoModel ? $this->user()->can('update', $egresoModel) : false;
    }

    public function rules(): array
    {
        return [
            'caja_id' => ['required', 'integer', 'exists:cajas,id'],
            'fecha' => ['required', 'date'],
            'cuenta_egreso_id' => ['required', 'integer', 'exists:catalogo_egresos,id'],
            'monto' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'descripcion' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:100'],
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

            $egreso = $this->route('egreso');
            $egresoModel = $egreso instanceof Egreso ? $egreso : Egreso::find($egreso);
            $cuentaId = $this->input('cuenta_egreso_id');

            if ($cuentaId) {
                $cuenta = CatalogoEgreso::find($cuentaId);
                if ($cuenta && ! $cuenta->activo && $cuentaId != $egresoModel?->cuenta_egreso_id) {
                    $v->errors()->add('cuenta_egreso_id', 'La cuenta de egreso seleccionada está inactiva.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'caja_id.required' => 'Debe seleccionar una caja contable.',
            'cuenta_egreso_id.required' => 'Debe seleccionar una cuenta de egreso.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'monto.decimal' => 'El monto no puede tener más de 2 decimales.',
            'descripcion.required' => 'La descripción del gasto o egreso es obligatoria.',
        ];
    }
}
