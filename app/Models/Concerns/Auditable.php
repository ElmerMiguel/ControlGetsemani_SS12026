<?php

namespace App\Models\Concerns;

use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait Auditable
 *
 * Implementa RN-16: Bitácora de auditoría automática para modelos Eloquent.
 * Engancha los eventos created, updated, deleted y restored, registrando cambios
 * reales y protegiendo campos sensibles (password, remember_token).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $service = app(BitacoraService::class);
            $nombre = static::obtenerNombreLegibleModelo($model);
            $descripcion = "Creó {$nombre} #{$model->getKey()}";

            $despues = $model->getAttributes();

            $service->registrar('crear', $model, $descripcion, null, $despues);
        });

        static::updated(function (Model $model) {
            $service = app(BitacoraService::class);
            $dirty = $model->getDirty();

            $excluidos = config('auditoria.campos_excluidos', [
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $cambioPassword = array_key_exists('password', $dirty);

            // Remover campos excluidos del arreglo dirty
            $dirtyFiltrado = array_diff_key($dirty, array_flip($excluidos));

            // Si no hubo cambios en campos auditables ni cambio de password, no registrar nada
            if (empty($dirtyFiltrado) && ! $cambioPassword) {
                return;
            }

            $nombre = static::obtenerNombreLegibleModelo($model);

            // Si el único cambio fue la contraseña
            if (empty($dirtyFiltrado) && $cambioPassword) {
                $descripcion = "Cambió la contraseña de {$nombre} #{$model->getKey()}";
                $service->registrar('modificar', $model, $descripcion, null, null);

                return;
            }

            $antes = [];
            $despues = [];
            $cambiosTexto = [];

            foreach ($dirtyFiltrado as $campo => $nuevoValor) {
                $valorAnterior = $model->getOriginal($campo);
                $antes[$campo] = $valorAnterior;
                $despues[$campo] = $nuevoValor;

                $strAntes = is_scalar($valorAnterior) ? (string) $valorAnterior : json_encode($valorAnterior);
                $strNuevo = is_scalar($nuevoValor) ? (string) $nuevoValor : json_encode($nuevoValor);

                $cambiosTexto[] = "{$campo} {$strAntes} → {$strNuevo}";
            }

            $detalle = implode(', ', $cambiosTexto);
            if ($cambioPassword) {
                $detalle = 'cambió la contraseña, '.$detalle;
            }

            $descripcion = "Modificó {$nombre} #{$model->getKey()}: {$detalle}";

            $service->registrar('modificar', $model, $descripcion, $antes, $despues);
        });

        static::deleted(function (Model $model) {
            $service = app(BitacoraService::class);
            $nombre = static::obtenerNombreLegibleModelo($model);

            $esSoftDelete = in_array(SoftDeletes::class, class_uses_recursive($model))
                && (! method_exists($model, 'isForceDeleting') || ! $model->isForceDeleting());

            $esMovimiento = in_array(class_basename($model), ['Ingreso', 'Egreso', 'Transferencia']);
            $accion = ($esSoftDelete && $esMovimiento) ? 'anular' : 'eliminar';
            $verbo = ($esSoftDelete && $esMovimiento) ? 'Anuló' : 'Eliminó';

            $descripcion = "{$verbo} {$nombre} #{$model->getKey()}";
            $antes = $model->getAttributes();

            $service->registrar($accion, $model, $descripcion, $antes, null);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                $service = app(BitacoraService::class);
                $nombre = static::obtenerNombreLegibleModelo($model);
                $descripcion = "Restauró {$nombre} #{$model->getKey()}";

                $service->registrar('restaurar', $model, $descripcion, null, $model->getAttributes());
            });
        }
    }

    /**
     * Devuelve el nombre en español legible del modelo para la descripción de bitácora.
     */
    protected static function obtenerNombreLegibleModelo(Model $model): string
    {
        $clase = class_basename($model);

        return match ($clase) {
            'User' => 'Usuario',
            'Departamento' => 'Departamento',
            'Caja' => 'Caja',
            'CatalogoIngreso' => 'Cuenta de Ingreso',
            'CatalogoEgreso' => 'Cuenta de Egreso',
            'Aportante' => 'Aportante',
            'Ingreso' => 'Ingreso',
            'Egreso' => 'Egreso',
            'Transferencia' => 'Transferencia',
            'CorteCaja' => 'Corte de Caja',
            default => $clase,
        };
    }
}
