<?php

namespace App\Http\Requests;

use App\Enums\MedioCaja;
use App\Models\Caja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cajas.gestionar');
    }

    public function rules(): array
    {
        $caja = $this->route('caja');
        $cajaModel = $caja instanceof Caja ? $caja : Caja::find($caja);
        $cajaId = $cajaModel?->id;

        $deptoId = $this->input('departamento_id', $cajaModel?->departamento_id);

        return [
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cajas', 'nombre')
                    ->where('departamento_id', $deptoId)
                    ->ignore($cajaId),
            ],
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('cajas', 'codigo')->ignore($cajaId),
            ],
            'medio' => ['required', new Enum(MedioCaja::class)],
            'saldo_apertura' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'fecha_apertura' => ['sometimes', 'date'],
            'activa' => ['sometimes', 'boolean'],
            'tesoreros' => ['nullable', 'array'],
            'tesoreros.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $caja = $this->route('caja');
            $cajaModel = $caja instanceof Caja ? $caja : Caja::find($caja);

            if ($cajaModel && $cajaModel->tieneMovimientos()) {
                if ($this->has('saldo_apertura')) {
                    $nuevoSaldo = number_format((float) $this->input('saldo_apertura'), 2, '.', '');
                    $actualSaldo = number_format((float) $cajaModel->saldo_apertura, 2, '.', '');
                    if ($nuevoSaldo !== $actualSaldo) {
                        $v->errors()->add('saldo_apertura', 'No es posible modificar el saldo de apertura porque la caja ya registra movimientos contables o cortes.');
                    }
                }

                if ($this->has('fecha_apertura')) {
                    $nuevaFecha = date('Y-m-d', strtotime($this->input('fecha_apertura')));
                    $actualFecha = $cajaModel->fecha_apertura?->format('Y-m-d');
                    if ($nuevaFecha !== $actualFecha) {
                        $v->errors()->add('fecha_apertura', 'No es posible modificar la fecha de apertura porque la caja ya registra movimientos contables o cortes.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una caja con este nombre en el departamento seleccionado.',
            'codigo.unique' => 'El código asignado ya pertenece a otra caja registrada.',
            'saldo_apertura.decimal' => 'El saldo de apertura debe tener un máximo de 2 decimales.',
        ];
    }
}
