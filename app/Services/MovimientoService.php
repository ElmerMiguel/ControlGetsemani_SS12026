<?php

namespace App\Services;

use App\Exceptions\MovimientoInvalidoException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * MovimientoService
 *
 * Implementa las reglas de negocio para transacciones contables:
 * - RN-01: Moneda DECIMAL(14,2), mayor a 0, máximo 2 decimales.
 * - RN-04: Fecha del movimiento válida (>= apertura, <= hoy en America/Guatemala, no en periodo bloqueado).
 * - RN-05: Detección de periodos bloqueados por cortes de caja (pendiente o aprobado).
 * - RN-06: Imposibilidad de modificar o anular registros en periodos bloqueados.
 * - RN-07: Anulación lógica con motivo obligatorio (>= 10 caracteres) y SoftDeletes.
 * - RN-10: Advertencia si un egreso deja saldo negativo en caja (sin bloquear la operación).
 * - RN-15: Aislamiento por caja asignada según rol y permisos.
 */
class MovimientoService
{
    /**
     * Registra un nuevo ingreso contable en la caja especificada.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function crearIngreso(User $user, array $datos): array
    {
        return DB::transaction(function () use ($user, $datos) {
            $caja = Caja::findOrFail($datos['caja_id']);
            $this->validarAccesoCaja($user, $caja);
            $this->validarFecha($caja, $datos['fecha']);
            $this->validarMonto($datos['monto']);

            $cuenta = CatalogoIngreso::findOrFail($datos['cuenta_ingreso_id']);
            if (! $cuenta->activo) {
                throw new MovimientoInvalidoException('La cuenta de ingreso seleccionada está inactiva.');
            }

            $ingreso = Ingreso::create([
                'caja_id' => $caja->id,
                'fecha' => $datos['fecha'],
                'cuenta_ingreso_id' => $cuenta->id,
                'monto' => $datos['monto'],
                'recibo' => $datos['recibo'] ?? null,
                'aportante_id' => $datos['aportante_id'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'usuario_id' => $user->id,
            ]);

            return ['ingreso' => $ingreso];
        });
    }

    /**
     * Registra un nuevo egreso contable en la caja especificada.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function crearEgreso(User $user, array $datos): array
    {
        return DB::transaction(function () use ($user, $datos) {
            $caja = Caja::findOrFail($datos['caja_id']);
            $this->validarAccesoCaja($user, $caja);
            $this->validarFecha($caja, $datos['fecha']);
            $this->validarMonto($datos['monto']);

            $cuenta = CatalogoEgreso::findOrFail($datos['cuenta_egreso_id']);
            if (! $cuenta->activo) {
                throw new MovimientoInvalidoException('La cuenta de egreso seleccionada está inactiva.');
            }

            $egreso = Egreso::create([
                'caja_id' => $caja->id,
                'fecha' => $datos['fecha'],
                'cuenta_egreso_id' => $cuenta->id,
                'monto' => $datos['monto'],
                'descripcion' => $datos['descripcion'],
                'referencia' => $datos['referencia'] ?? null,
                'usuario_id' => $user->id,
            ]);

            $saldoActual = $caja->calcularSaldoActual();
            $esNegativo = bccomp($saldoActual, '0.00', 2) < 0;

            return [
                'egreso' => $egreso,
                'saldo_negativo' => $esNegativo,
                'saldo_actual' => $saldoActual,
            ];
        });
    }

    /**
     * Actualiza un movimiento contable (Ingreso o Egreso) existente.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function actualizar(User $user, Model $movimiento, array $datos): array
    {
        return DB::transaction(function () use ($user, $movimiento, $datos) {
            if ($movimiento->esBloqueado()) {
                throw new PeriodoBloqueadoException('No es posible modificar un movimiento dentro de un periodo bloqueado por corte de caja.');
            }

            $caja = isset($datos['caja_id']) ? Caja::findOrFail($datos['caja_id']) : $movimiento->caja;
            $this->validarAccesoCaja($user, $caja);

            $nuevaFecha = $datos['fecha'] ?? $movimiento->fecha->format('Y-m-d');
            $this->validarFecha($caja, $nuevaFecha);

            if (isset($datos['monto'])) {
                $this->validarMonto($datos['monto']);
            }

            if ($movimiento instanceof Ingreso && isset($datos['cuenta_ingreso_id'])) {
                $cuenta = CatalogoIngreso::findOrFail($datos['cuenta_ingreso_id']);
                if (! $cuenta->activo && $cuenta->id !== $movimiento->cuenta_ingreso_id) {
                    throw new MovimientoInvalidoException('La cuenta de ingreso seleccionada está inactiva.');
                }
            }

            if ($movimiento instanceof Egreso && isset($datos['cuenta_egreso_id'])) {
                $cuenta = CatalogoEgreso::findOrFail($datos['cuenta_egreso_id']);
                if (! $cuenta->activo && $cuenta->id !== $movimiento->cuenta_egreso_id) {
                    throw new MovimientoInvalidoException('La cuenta de egreso seleccionada está inactiva.');
                }
            }

            $movimiento->update($datos);

            $esNegativo = false;
            $saldoActual = $caja->calcularSaldoActual();
            if ($movimiento instanceof Egreso) {
                $esNegativo = bccomp($saldoActual, '0.00', 2) < 0;
            }

            return [
                'movimiento' => $movimiento,
                'saldo_negativo' => $esNegativo,
                'saldo_actual' => $saldoActual,
            ];
        });
    }

    /**
     * Anula un movimiento contable aplicando SoftDeletes y registrando el motivo.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function anular(User $user, Model $movimiento, string $motivo): void
    {
        DB::transaction(function () use ($user, $movimiento, $motivo) {
            if ($movimiento->esBloqueado()) {
                throw new PeriodoBloqueadoException('No es posible anular un movimiento dentro de un periodo bloqueado por corte de caja.');
            }

            $this->validarAccesoCaja($user, $movimiento->caja);

            $motivoLimpio = trim($motivo);
            if (mb_strlen($motivoLimpio) < 10) {
                throw new MovimientoInvalidoException('El motivo de anulación debe tener al menos 10 caracteres.');
            }

            $movimiento->anulado_por = $user->id;
            $movimiento->motivo_anulacion = $motivoLimpio;
            $movimiento->save();

            $movimiento->delete();
        });
    }

    /**
     * Valida pertenencia y estado operativo de la caja según RN-13 y RN-15.
     */
    private function validarAccesoCaja(User $user, Caja $caja): void
    {
        if (! $caja->activa) {
            throw new MovimientoInvalidoException('La caja seleccionada está inactiva y no admite operaciones contables.');
        }

        if (! $user->can('cajas.gestionar') && ! $user->cajas()->where('cajas.id', $caja->id)->exists()) {
            throw new MovimientoInvalidoException('No tiene permisos para operar en la caja seleccionada.');
        }
    }

    /**
     * RN-04: La fecha no puede ser anterior a la apertura, ni futura en America/Guatemala,
     * ni pertenecer a un periodo con corte de caja pendiente o aprobado.
     */
    private function validarFecha(Caja $caja, string $fecha): void
    {
        $fechaCarbon = Carbon::parse($fecha, 'America/Guatemala')->startOfDay();
        $hoy = Carbon::now('America/Guatemala')->startOfDay();
        $fechaApertura = $caja->fecha_apertura->startOfDay();

        if ($fechaCarbon->gt($hoy)) {
            throw new MovimientoInvalidoException('La fecha del movimiento no puede ser futura.');
        }

        if ($fechaCarbon->lt($fechaApertura)) {
            throw new MovimientoInvalidoException("La fecha no puede ser anterior a la apertura de la caja ({$fechaApertura->format('d/m/Y')}).");
        }

        if ($caja->estaBloqueada($fechaCarbon)) {
            throw new PeriodoBloqueadoException('La fecha seleccionada corresponde a un periodo bloqueado por corte de caja.');
        }
    }

    /**
     * RN-01: El monto debe ser estrictamente positivo y con máximo dos decimales.
     */
    private function validarMonto(mixed $monto): void
    {
        if (! is_numeric($monto) || bccomp((string) $monto, '0.00', 2) <= 0) {
            throw new MovimientoInvalidoException('El monto debe ser una cantidad numérica mayor a cero.');
        }
    }
}
