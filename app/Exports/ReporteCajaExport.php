<?php

namespace App\Exports;

use App\Exports\Sheets\EgresosSheet;
use App\Exports\Sheets\IngresosSheet;
use App\Exports\Sheets\MatrizSheet;
use App\Exports\Sheets\ResumenSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * ReporteCajaExport
 *
 * Exportación a formato Microsoft Excel (.xlsx) con múltiples hojas contables:
 * - Resumen: Balance y estado general del período.
 * - Ingresos: Cuentas de ingreso ordinarias y transferencias recibidas.
 * - Egresos: Cuentas de egreso ordinarias y transferencias enviadas.
 * - Matriz: Matriz de egresos cuenta × mes con totales cruzados.
 */
class ReporteCajaExport implements WithMultipleSheets
{
    public function __construct(
        protected array $datos
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function sheets(): array
    {
        return [
            new ResumenSheet($this->datos),
            new IngresosSheet($this->datos),
            new EgresosSheet($this->datos),
            new MatrizSheet($this->datos),
        ];
    }
}
