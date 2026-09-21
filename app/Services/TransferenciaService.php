<?php

namespace App\Services;

use App\Exceptions\MovimientoInvalidoException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * TransferenciaService
 *
 * Implementa RN-11: Transferencias entre cajas de la institución.
 * - En una sola transacción atómica crea un egreso (cuenta 900) en el origen y un ingreso (cuenta 900) en el destino.
 * - Regla origen ≠ destino.
 * - Vinculación atómica con transferencia_id.
 * - Anulación en cascada lógica (SoftDeletes) con motivo (RN-07) preservando inmutabilidad contable.
 * - Verificación de bloqueo de período en ambas cajas (RN-05).
 */
class TransferenciaService
{
    public function __construct(
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Registra una nueva transferencia entre dos cajas.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function crear(User $user, array $datos): Transferencia
    {
        if ($datos['caja_origen_id'] == $datos['caja_destino_id']) {
            throw new MovimientoInvalidoException('La caja de origen y destino deben ser distintas.');
        }

        $cajaOrigen = Caja::findOrFail($datos['caja_origen_id']);
        $cajaDestino = Caja::findOrFail($datos['caja_destino_id']);

        if (! $cajaOrigen->activa || ! $cajaDestino->activa) {
            throw new MovimientoInvalidoException('Ambas cajas deben estar activas para realizar transferencias.');
        }

        // Validación de permisos de acceso en origen
        if (! $user->can('cajas.gestionar') && ! $user->cajas()->where('cajas.id', $cajaOrigen->id)->exists()) {
            throw new MovimientoInvalidoException('No tiene autorización para transferir fondos desde la caja de origen.');
        }

        $this->validarMonto($datos['monto']);
        $this->validarFechaTransferencia($cajaOrigen, $cajaDestino, $datos['fecha']);

        $concepto = trim($datos['concepto'] ?? 'Transferencia de fondos entre cajas');

        return DB::transaction(function () use ($user, $cajaOrigen, $cajaDestino, $datos, $concepto) {
            $cuentaEgreso = CatalogoEgreso::where('codigo', '900')
                ->orWhere('es_transferencia', true)
                ->firstOrFail();

            $cuentaIngreso = CatalogoIngreso::where('codigo', '900')
                ->orWhere('es_transferencia', true)
                ->firstOrFail();

            $transferencia = Transferencia::create([
                'caja_origen_id' => $cajaOrigen->id,
                'caja_destino_id' => $cajaDestino->id,
                'fecha' => $datos['fecha'],
                'monto' => $datos['monto'],
                'concepto' => $concepto,
                'usuario_id' => $user->id,
            ]);

            // Egreso en caja origen
            Egreso::create([
                'caja_id' => $cajaOrigen->id,
                'fecha' => $datos['fecha'],
                'cuenta_egreso_id' => $cuentaEgreso->id,
                'monto' => $datos['monto'],
                'descripcion' => "Transferencia enviada a {$cajaDestino->nombre}: {$concepto}",
                'referencia' => $concepto,
                'usuario_id' => $user->id,
                'transferencia_id' => $transferencia->id,
            ]);

            // Ingreso en caja destino
            Ingreso::create([
                'caja_id' => $cajaDestino->id,
                'fecha' => $datos['fecha'],
                'cuenta_ingreso_id' => $cuentaIngreso->id,
                'monto' => $datos['monto'],
                'observaciones' => "Transferencia recibida de {$cajaOrigen->nombre}: {$concepto}",
                'usuario_id' => $user->id,
                'transferencia_id' => $transferencia->id,
            ]);

            $this->bitacoraService->registrar(
                'transferencia.creada',
                $transferencia,
                "Transferencia creada de {$cajaOrigen->codigo} a {$cajaDestino->codigo} por Q {$datos['monto']}",
                null,
                $transferencia->toArray(),
                $user->id
            );

            return $transferencia;
        });
    }

    /**
     * Anula una transferencia y simultáneamente sus movimientos correspondientes de ingreso y egreso.
     *
     * @throws MovimientoInvalidoException|PeriodoBloqueadoException
     */
    public function anular(User $user, Transferencia $transferencia, string $motivo): void
    {
        $motivoLimpio = trim($motivo);
        if (mb_strlen($motivoLimpio) < 10) {
            throw new MovimientoInvalidoException('El motivo de anulación debe contener al menos 10 caracteres.');
        }

        $fechaCarbon = Carbon::parse($transferencia->fecha, 'America/Guatemala')->startOfDay();
        if ($transferencia->cajaOrigen->estaBloqueada($fechaCarbon) || $transferencia->cajaDestino->estaBloqueada($fechaCarbon)) {
            throw new PeriodoBloqueadoException('No es posible anular la transferencia: una de las cajas tiene un período bloqueado por corte.');
        }

        DB::transaction(function () use ($user, $transferencia, $motivoLimpio) {
            $antes = $transferencia->toArray();

            $transferencia->anulado_por = $user->id;
            $transferencia->motivo_anulacion = $motivoLimpio;
            $transferencia->save();
            $transferencia->delete();

            // Anular ingresos asociados
            foreach ($transferencia->ingresos as $ingreso) {
                $ingreso->anulado_por = $user->id;
                $ingreso->motivo_anulacion = "Transferencia anulada: {$motivoLimpio}";
                $ingreso->save();
                $ingreso->delete();
            }

            // Anular egresos asociados
            foreach ($transferencia->egresos as $egreso) {
                $egreso->anulado_por = $user->id;
                $egreso->motivo_anulacion = "Transferencia anulada: {$motivoLimpio}";
                $egreso->save();
                $egreso->delete();
            }

            $this->bitacoraService->registrar(
                'transferencia.anulada',
                $transferencia,
                "Transferencia #{$transferencia->id} anulada: {$motivoLimpio}",
                $antes,
                $transferencia->toArray(),
                $user->id
            );
        });
    }

    protected function validarMonto(mixed $monto): void
    {
        if (! is_numeric($monto) || bccomp((string) $monto, '0.00', 2) <= 0) {
            throw new MovimientoInvalidoException('El monto transferido debe ser mayor a cero.');
        }
    }

    protected function validarFechaTransferencia(Caja $cajaOrigen, Caja $cajaDestino, string $fecha): void
    {
        $fechaCarbon = Carbon::parse($fecha, 'America/Guatemala')->startOfDay();
        $hoy = Carbon::now('America/Guatemala')->startOfDay();

        if ($fechaCarbon->gt($hoy)) {
            throw new MovimientoInvalidoException('La fecha de la transferencia no puede ser futura.');
        }

        $aperturaOrigen = $cajaOrigen->fecha_apertura->startOfDay();
        $aperturaDestino = $cajaDestino->fecha_apertura->startOfDay();

        if ($fechaCarbon->lt($aperturaOrigen) || $fechaCarbon->lt($aperturaDestino)) {
            throw new MovimientoInvalidoException('La fecha no puede ser anterior a la apertura de las cajas participantes.');
        }

        if ($cajaOrigen->estaBloqueada($fechaCarbon) || $cajaDestino->estaBloqueada($fechaCarbon)) {
            throw new PeriodoBloqueadoException('La fecha seleccionada corresponde a un período bloqueado por corte en una de las cajas.');
        }
    }
}
