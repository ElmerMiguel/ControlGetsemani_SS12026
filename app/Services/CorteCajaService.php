<?php

namespace App\Services;

use App\Enums\EstadoCorte;
use App\Exceptions\DescuadreSnapshotException;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\User;
use App\Notifications\CorteAprobadoNotification;
use App\Notifications\CorteReabiertoNotification;
use App\Notifications\CorteRechazadoNotification;
use App\Notifications\CorteSolicitadoNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestión del ciclo de vida y máquina de estados del Corte de Caja.
 *
 * Implementa las reglas de negocio del proceso complejo:
 * - RN-05: Período bloqueado por corte en estado pendiente o aprobado.
 * - RN-08: Solicitud de corte, continuidad contable (inicio = día siguiente al último fin aprobado),
 *          cálculo y persistencia inmutable del snapshot contable, y restricción de un único corte pendiente por caja.
 * - RN-09: Revisión por Administrador General:
 *          - Aprobación: recalcula el snapshot en la transacción (debe coincidir exactamente).
 *          - Rechazo: observación obligatoria (>= 10 caracteres), desbloquea el período.
 *          - Reapertura: exclusivamente sobre el último corte aprobado de la caja, motivo obligatorio (>= 10 caracteres).
 * - RN-15: Aislamiento por usuario y cajas asignadas.
 * - RN-16: Auditoría inmutable de transiciones de estado en bitácora.
 */
class CorteCajaService
{
    public function __construct(
        protected CajaService $cajaService,
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * RN-08: Determina el período sugerido para un nuevo corte de caja.
     * El inicio corresponde al día siguiente al periodo_fin del último corte aprobado,
     * o a la fecha_apertura de la caja si aún no cuenta con cortes aprobados.
     */
    public function periodoSugerido(Caja $caja): array
    {
        $ultimoAprobado = $caja->cortesCaja()
            ->where('estado', EstadoCorte::Aprobado)
            ->latest('periodo_fin')
            ->first();

        if ($ultimoAprobado && $ultimoAprobado->periodo_fin) {
            $inicio = $ultimoAprobado->periodo_fin->copy()->addDay();
        } else {
            $inicio = $caja->fecha_apertura ? $caja->fecha_apertura->copy() : Carbon::create(now()->year, 1, 1);
        }

        $hoy = Carbon::today();

        return [
            'inicio' => $inicio,
            'fin_max' => $hoy,
        ];
    }

    /**
     * Calcula la vista previa del snapshot contable antes de persistir la solicitud.
     */
    public function previewSnapshot(Caja $caja, CarbonInterface|string $fin): array
    {
        $sugerido = $this->periodoSugerido($caja);
        $inicio = $sugerido['inicio'];
        $fechaFin = is_string($fin) ? Carbon::parse($fin) : $fin;

        if ($fechaFin->lt($inicio)) {
            throw ValidationException::withMessages([
                'periodo_fin' => "La fecha fin no puede ser anterior a la fecha de inicio ({$inicio->format('d/m/Y')}).",
            ]);
        }

        if ($fechaFin->gt(Carbon::today())) {
            throw ValidationException::withMessages([
                'periodo_fin' => 'La fecha fin no puede ser futura.',
            ]);
        }

        $resumen = $this->cajaService->resumenPeriodo($caja, $inicio, $fechaFin);

        return [
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fechaFin->format('Y-m-d'),
            'saldo_inicial' => $resumen['saldo_inicial'],
            'total_ingresos' => $resumen['ingresos'],
            'total_egresos' => $resumen['egresos'],
            'saldo_final' => $resumen['saldo_final'],
        ];
    }

    /**
     * RN-08: Solicita un nuevo corte de caja y bloquea el período (RN-05).
     */
    public function solicitar(User $user, Caja $caja, CarbonInterface|string $fin, ?string $observaciones = null): CorteCaja
    {
        return DB::transaction(function () use ($user, $caja, $fin, $observaciones) {
            // Bloqueo pesimista para evitar carreras o cortes simultáneos
            $cajaBloqueada = Caja::where('id', $caja->id)->lockForUpdate()->firstOrFail();

            // 1. Validar que no exista otro corte en estado pendiente para esta caja
            $tienePendiente = $cajaBloqueada->cortesCaja()
                ->where('estado', EstadoCorte::Pendiente)
                ->exists();

            if ($tienePendiente) {
                throw ValidationException::withMessages([
                    'caja_id' => 'Ya existe un corte pendiente de revisión para esta caja.',
                ]);
            }

            // 2. Determinar fechas de inicio y fin
            $sugerido = $this->periodoSugerido($cajaBloqueada);
            $inicio = $sugerido['inicio'];
            $fechaFin = is_string($fin) ? Carbon::parse($fin) : $fin;

            if ($fechaFin->lt($inicio)) {
                throw ValidationException::withMessages([
                    'periodo_fin' => "La fecha fin no puede ser anterior a la fecha de inicio del período ({$inicio->format('d/m/Y')}).",
                ]);
            }

            if ($fechaFin->gt(Carbon::today())) {
                throw ValidationException::withMessages([
                    'periodo_fin' => 'La fecha fin no puede ser futura.',
                ]);
            }

            // 3. Calcular snapshot mediante CajaService (RN-03)
            $snapshot = $this->cajaService->resumenPeriodo($cajaBloqueada, $inicio, $fechaFin);

            // 4. Crear el registro en estado pendiente
            $corte = CorteCaja::create([
                'caja_id' => $cajaBloqueada->id,
                'periodo_inicio' => $inicio->format('Y-m-d'),
                'periodo_fin' => $fechaFin->format('Y-m-d'),
                'estado' => EstadoCorte::Pendiente,
                'saldo_inicial' => $snapshot['saldo_inicial'],
                'total_ingresos' => $snapshot['ingresos'],
                'total_egresos' => $snapshot['egresos'],
                'saldo_final' => $snapshot['saldo_final'],
                'solicitado_por' => $user->id,
                'solicitado_at' => now(),
                'observaciones' => $observaciones,
            ]);

            // 5. Bitácora de auditoría inmutable
            $this->bitacoraService->registrar(
                accion: 'corte.solicitado',
                modelo: $corte,
                descripcion: "Corte de caja solicitado para {$cajaBloqueada->nombre} ({$corte->periodo_inicio->format('d/m/Y')} al {$corte->periodo_fin->format('d/m/Y')})",
                antes: null,
                despues: ['estado' => 'pendiente', 'saldo_final' => $corte->saldo_final],
                usuarioId: $user->id
            );

            // 6. Notificar a todos los administradores activos
            $admins = User::role('admin')->where('activo', true)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CorteSolicitadoNotification($corte));
            }

            return $corte;
        });
    }

    /**
     * RN-09: Aprueba un corte de caja pendiente.
     * Recalcula el snapshot en la transacción y valida que coincida con el guardado.
     */
    public function aprobar(User $admin, CorteCaja $corte): CorteCaja
    {
        return DB::transaction(function () use ($admin, $corte) {
            $corteBloqueado = CorteCaja::where('id', $corte->id)->lockForUpdate()->firstOrFail();
            $cajaBloqueada = Caja::where('id', $corteBloqueado->caja_id)->lockForUpdate()->firstOrFail();

            if ($corteBloqueado->estado !== EstadoCorte::Pendiente) {
                throw new TransicionInvalidaException(
                    "Solo se pueden aprobar cortes en estado pendiente. Estado actual: {$corteBloqueado->estado->value}."
                );
            }

            // Recalcular snapshot contable
            $recalculado = $this->cajaService->resumenPeriodo(
                $cajaBloqueada,
                $corteBloqueado->periodo_inicio,
                $corteBloqueado->periodo_fin
            );

            // Validar coincidencia estricta de saldos
            $coincide = bccomp((string) $corteBloqueado->saldo_inicial, (string) $recalculado['saldo_inicial'], 2) === 0
                && bccomp((string) $corteBloqueado->total_ingresos, (string) $recalculado['ingresos'], 2) === 0
                && bccomp((string) $corteBloqueado->total_egresos, (string) $recalculado['egresos'], 2) === 0
                && bccomp((string) $corteBloqueado->saldo_final, (string) $recalculado['saldo_final'], 2) === 0;

            if (! $coincide) {
                throw new DescuadreSnapshotException(
                    'El snapshot recalculado difiere del guardado al solicitar el corte. Verifique que no hayan existido modificaciones externas.'
                );
            }

            $estadoAnterior = $corteBloqueado->estado->value;

            $corteBloqueado->estado = EstadoCorte::Aprobado;
            $corteBloqueado->revisado_por = $admin->id;
            $corteBloqueado->revisado_at = now();
            $corteBloqueado->save();

            // Bitácora
            $this->bitacoraService->registrar(
                accion: 'corte.aprobado',
                modelo: $corteBloqueado,
                descripcion: "Corte de caja aprobado para {$cajaBloqueada->nombre} ({$corteBloqueado->periodo_inicio->format('d/m/Y')} al {$corteBloqueado->periodo_fin->format('d/m/Y')})",
                antes: ['estado' => $estadoAnterior],
                despues: ['estado' => 'aprobado'],
                usuarioId: $admin->id
            );

            // Notificar al solicitante
            if ($corteBloqueado->solicitante) {
                $corteBloqueado->solicitante->notify(new CorteAprobadoNotification($corteBloqueado));
            }

            return $corteBloqueado;
        });
    }

    /**
     * RN-09: Rechaza un corte de caja pendiente con observación obligatoria.
     * Desbloquea el período para que el tesorero pueda corregir y solicitar de nuevo.
     */
    public function rechazar(User $admin, CorteCaja $corte, string $observacion): CorteCaja
    {
        if (mb_strlen(trim($observacion)) < 10) {
            throw ValidationException::withMessages([
                'observaciones' => 'La observación de rechazo debe contener al menos 10 caracteres explicativos.',
            ]);
        }

        return DB::transaction(function () use ($admin, $corte, $observacion) {
            $corteBloqueado = CorteCaja::where('id', $corte->id)->lockForUpdate()->firstOrFail();
            $cajaBloqueada = Caja::where('id', $corteBloqueado->caja_id)->lockForUpdate()->firstOrFail();

            if ($corteBloqueado->estado !== EstadoCorte::Pendiente) {
                throw new TransicionInvalidaException(
                    "Solo se pueden rechazar cortes en estado pendiente. Estado actual: {$corteBloqueado->estado->value}."
                );
            }

            $estadoAnterior = $corteBloqueado->estado->value;

            $corteBloqueado->estado = EstadoCorte::Rechazado;
            $corteBloqueado->observaciones = trim($observacion);
            $corteBloqueado->revisado_por = $admin->id;
            $corteBloqueado->revisado_at = now();
            $corteBloqueado->save();

            // Bitácora
            $this->bitacoraService->registrar(
                accion: 'corte.rechazado',
                modelo: $corteBloqueado,
                descripcion: "Corte de caja rechazado para {$cajaBloqueada->nombre} ({$corteBloqueado->periodo_inicio->format('d/m/Y')} al {$corteBloqueado->periodo_fin->format('d/m/Y')})",
                antes: ['estado' => $estadoAnterior],
                despues: ['estado' => 'rechazado', 'observaciones' => $corteBloqueado->observaciones],
                usuarioId: $admin->id
            );

            // Notificar al solicitante
            if ($corteBloqueado->solicitante) {
                $corteBloqueado->solicitante->notify(new CorteRechazadoNotification($corteBloqueado));
            }

            return $corteBloqueado;
        });
    }

    /**
     * RN-09: Reabre el último corte aprobado de la caja con motivo obligatorio.
     * Desbloquea el período para ajustes y correcciones.
     */
    public function reabrir(User $admin, CorteCaja $corte, string $motivo): CorteCaja
    {
        if (mb_strlen(trim($motivo)) < 10) {
            throw ValidationException::withMessages([
                'observaciones' => 'El motivo de reapertura debe contener al menos 10 caracteres explicativos.',
            ]);
        }

        return DB::transaction(function () use ($admin, $corte, $motivo) {
            $corteBloqueado = CorteCaja::where('id', $corte->id)->lockForUpdate()->firstOrFail();
            $cajaBloqueada = Caja::where('id', $corteBloqueado->caja_id)->lockForUpdate()->firstOrFail();

            if ($corteBloqueado->estado !== EstadoCorte::Aprobado) {
                throw new TransicionInvalidaException(
                    "Solo se pueden reabrir cortes en estado aprobado. Estado actual: {$corteBloqueado->estado->value}."
                );
            }

            // Validar que sea estrictamente el último corte aprobado de la caja
            $ultimoAprobadoId = CorteCaja::where('caja_id', $corteBloqueado->caja_id)
                ->where('estado', EstadoCorte::Aprobado)
                ->latest('periodo_fin')
                ->latest('id')
                ->value('id');

            if ($corteBloqueado->id !== $ultimoAprobadoId) {
                throw new TransicionInvalidaException(
                    'Solo es posible reabrir el último corte aprobado de la caja para salvaguardar la correlatividad de saldos.'
                );
            }

            $estadoAnterior = $corteBloqueado->estado->value;

            $obsPrevias = $corteBloqueado->observaciones;
            $nuevoHistorial = trim(($obsPrevias ? $obsPrevias."\n" : '').'[Reapertura]: '.trim($motivo));

            $corteBloqueado->estado = EstadoCorte::Reabierto;
            $corteBloqueado->observaciones = $nuevoHistorial;
            $corteBloqueado->revisado_por = $admin->id;
            $corteBloqueado->revisado_at = now();
            $corteBloqueado->save();

            // Bitácora
            $this->bitacoraService->registrar(
                accion: 'corte.reabierto',
                modelo: $corteBloqueado,
                descripcion: "Corte de caja reabierto para {$cajaBloqueada->nombre} ({$corteBloqueado->periodo_inicio->format('d/m/Y')} al {$corteBloqueado->periodo_fin->format('d/m/Y')})",
                antes: ['estado' => $estadoAnterior],
                despues: ['estado' => 'reabierto', 'observaciones' => $corteBloqueado->observaciones],
                usuarioId: $admin->id
            );

            // Notificar al solicitante
            if ($corteBloqueado->solicitante) {
                $corteBloqueado->solicitante->notify(new CorteReabiertoNotification($corteBloqueado));
            }

            return $corteBloqueado;
        });
    }
}
