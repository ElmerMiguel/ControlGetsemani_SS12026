<?php

namespace App\Exports;

use App\Exports\Sheets\InformeDepartamentosSheet;
use App\Exports\Sheets\InformeTransferenciasSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * InformeGeneralExport
 *
 * Exportación consolidada para la Asamblea a Microsoft Excel (.xlsx):
 * - Hoja 1: Consolidado por departamento con saldos y flujos ordinarios vs transferencias.
 * - Hoja 2: Movimientos y transferencias internas entre cajas (RN-11 / RN3-04).
 */
class InformeGeneralExport implements WithMultipleSheets
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
            new InformeDepartamentosSheet($this->datos),
            new InformeTransferenciasSheet($this->datos),
        ];
    }
}
