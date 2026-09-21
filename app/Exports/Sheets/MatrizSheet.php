<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MatrizSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Matriz';
    }

    public function array(): array
    {
        $matriz = $this->datos['matriz_egresos'];
        $meses = $matriz['meses'];

        $rows = [];

        // Encabezados
        $header = ['Código', 'Cuenta Contable'];
        foreach ($meses as $m) {
            $header[] = $m['nombre'];
        }
        $header[] = 'Total Cuenta';
        $rows[] = $header;

        // Filas por cuenta
        foreach ($matriz['filas'] as $fila) {
            $row = [
                $fila['codigo'],
                $fila['nombre'],
            ];
            foreach ($meses as $m) {
                $row[] = (float) ($fila['valores'][$m['clave']] ?? 0);
            }
            $row[] = (float) $fila['total'];
            $rows[] = $row;
        }

        // Fila de totales por mes
        $totalRow = ['TOTAL', 'TOTAL EGRESOS MENSUALES'];
        foreach ($meses as $m) {
            $totalRow[] = (float) ($matriz['totales_mes'][$m['clave']] ?? 0);
        }
        $totalRow[] = (float) $matriz['total_general'];
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $matriz = $this->datos['matriz_egresos'];
        $numMeses = count($matriz['meses']);
        $totalColumnas = 2 + $numMeses + 1; // Código + Cuenta + Meses + Total
        $columnaFinalLetra = Coordinate::stringFromColumnIndex($totalColumnas);

        $totalFilasCuentas = count($matriz['filas']);
        $filaTotal = 2 + $totalFilasCuentas;

        // Formato moneda desde columna C (3) hasta última columna
        $sheet->getStyle("C2:{$columnaFinalLetra}{$filaTotal}")->getNumberFormat()->setFormatCode('"Q "#,##0.00');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $filaTotal => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
        ];
    }
}
