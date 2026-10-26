<?php

namespace App\Services;

use App\Enums\EstadoBien;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service InventarioService
 *
 * Implementa las reglas de negocio de inventario institucional:
 * - RN3-01: No eliminar físicamente un bien con historial de estados; se desactiva/anula con SoftDeletes y motivo.
 * - Registro detallado de cambios de estado y motivos en bitácora.
 */
class InventarioService
{
    public function __construct(
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Registrar un nuevo bien institucional en inventario.
     */
    public function crear(User $user, array $datos): Inventario
    {
        return DB::transaction(function () use ($user, $datos) {
            $inventario = new Inventario;
            $inventario->departamento_id = $datos['departamento_id'];
            $inventario->codigo = strtoupper(trim($datos['codigo']));
            $inventario->nombre = trim($datos['nombre']);
            $inventario->descripcion = ! empty($datos['descripcion']) ? trim($datos['descripcion']) : null;
            $inventario->valor_estimado = isset($datos['valor_estimado']) && $datos['valor_estimado'] !== '' ? $datos['valor_estimado'] : null;
            $inventario->estado = $datos['estado'] instanceof EstadoBien ? $datos['estado'] : EstadoBien::from($datos['estado'] ?? 'correcto');
            $inventario->fecha_registro = $datos['fecha_registro'] ?? now()->toDateString();
            $inventario->usuario_id = $user->id;
            $inventario->save();

            return $inventario;
        });
    }

    /**
     * Actualizar datos descriptivos de un bien.
     */
    public function actualizar(Inventario $inventario, array $datos): Inventario
    {
        return DB::transaction(function () use ($inventario, $datos) {
            $inventario->departamento_id = $datos['departamento_id'];
            $inventario->codigo = strtoupper(trim($datos['codigo']));
            $inventario->nombre = trim($datos['nombre']);
            $inventario->descripcion = ! empty($datos['descripcion']) ? trim($datos['descripcion']) : null;
            $inventario->valor_estimado = isset($datos['valor_estimado']) && $datos['valor_estimado'] !== '' ? $datos['valor_estimado'] : null;
            $inventario->fecha_registro = $datos['fecha_registro'];
            $inventario->save();

            return $inventario;
        });
    }

    /**
     * Cambiar estado del bien (Correcto, Dañado, Perdido) registrando el motivo en bitácora.
     */
    public function cambiarEstado(Inventario $inventario, EstadoBien $nuevoEstado, string $motivo, User $user): Inventario
    {
        $estadoAnterior = $inventario->estado;

        if ($estadoAnterior === $nuevoEstado) {
            return $inventario;
        }

        return DB::transaction(function () use ($inventario, $nuevoEstado, $estadoAnterior, $motivo) {
            $inventario->estado = $nuevoEstado;
            $inventario->save();

            $this->bitacoraService->registrar(
                'editar',
                $inventario,
                "Cambio de estado del bien {$inventario->codigo} de {$estadoAnterior->label()} a {$nuevoEstado->label()}. Motivo: {$motivo}",
                ['estado' => $estadoAnterior->value],
                ['estado' => $nuevoEstado->value, 'motivo' => $motivo]
            );

            return $inventario;
        });
    }

    /**
     * RN3-01: Anular o dar de baja un bien con motivo explícito.
     * Si tiene historial de cambios de estado, se aplica SoftDeletes y se audita el motivo.
     */
    public function anular(Inventario $inventario, string $motivo, User $user): void
    {
        if (empty(trim($motivo))) {
            throw new InvalidArgumentException('Se requiere un motivo para dar de baja el bien.');
        }

        DB::transaction(function () use ($inventario, $motivo) {
            $this->bitacoraService->registrar(
                'anular',
                $inventario,
                "Baja/anulación de bien {$inventario->codigo} ({$inventario->nombre}). Motivo: {$motivo}",
                $inventario->getAttributes(),
                ['deleted_at' => now()->toDateTimeString(), 'motivo_baja' => $motivo]
            );

            $inventario->delete();
        });
    }

    /**
     * Obtiene el conteo total de bienes agrupados por estado para el dashboard.
     */
    public function resumenPorEstado(?User $user = null): array
    {
        $query = Inventario::query();

        if ($user && ! $user->hasRole('admin')) {
            $query->accesiblesPara($user);
        }

        $totales = $query->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        return [
            'total' => array_sum($totales),
            'correcto' => $totales[EstadoBien::Correcto->value] ?? 0,
            'danado' => $totales[EstadoBien::Danado->value] ?? 0,
            'perdido' => $totales[EstadoBien::Perdido->value] ?? 0,
        ];
    }
}
