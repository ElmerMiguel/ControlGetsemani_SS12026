<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IngresosSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Ingresos';
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado principal
        $rows[] = ['INGRESOS ORDINARIOS POR CUENTA'];
        $rows[] = ['Código', 'Cuenta Contable', 'Monto (Q)', 'Porcentaje (%)'];

        foreach ($this->datos['ingresos_por_cuenta'] as $item) {
            $rows[] = [
                $item['codigo'],
                $item['nombre'],
                (float) $item['total'],
                (float) $item['porcentaje'],
            ];
        }

        $rows[] = [
            'TOTAL',
            'TOTAL INGRESOS ORDINARIOS',
            (float) $this->datos['total_ingresos_cuentas'],
            100.00,
        ];

        // Transferencias si existen
        if (! empty($this->datos['transferencias']['recibidas'])) {
            $rows[] = [];
            $rows[] = ['TRANSFERENCIAS RECIBIDAS (INTERNAS)'];
            $rows[] = ['Fecha', 'Caja de Origen', 'Concepto / Motivo', 'Monto (Q)'];

            foreach ($this->datos['transferencias']['recibidas'] as $t) {
                $rows[] = [
                    $t['fecha'],
                    $t['caja_origen'],
                    $t['concepto'],
                    (float) $t['monto'],
                ];
            }

            $rows[] = [
                'TOTAL',
                'TOTAL TRANSFERENCIAS RECIBIDAS',
                '',
                (float) $this->datos['transferencias']['total_recibidas'],
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $totalCuentas = count($this->datos['ingresos_por_cuenta']);
        $filaTotalOrdinarios = 3 + $totalCuentas;

        // Formatos
        $sheet->getStyle('C3:C'.$filaTotalOrdinarios)->getNumberFormat()->setFormatCode('"Q "#,##0.00');
        $sheet->getStyle('D3:D'.$filaTotalOrdinarios)->getNumberFormat()->setFormatCode('0.00"%"');

        $sheet->mergeCells('A1:D1');

        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $filaTotalOrdinarios => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            ],
        ];

        if (! empty($this->datos['transferencias']['recibidas'])) {
            $filaTituloTransf = $filaTotalOrdinarios + 2;
            $filaHeaderTransf = $filaTituloTransf + 1;
            $filaTotalTransf = $filaHeaderTransf + count($this->datos['transferencias']['recibidas']) + 1;

            $sheet->mergeCells("A{$filaTituloTransf}:D{$filaTituloTransf}");
            $sheet->getStyle('D'.($filaHeaderTransf + 1).":D{$filaTotalTransf}")->getNumberFormat()->setFormatCode('"Q "#,##0.00');

            $styles[$filaTituloTransf] = ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E293B']]];
            $styles[$filaHeaderTransf] = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            ];
            $styles[$filaTotalTransf] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
            ];
        }

        return $styles;
    }
}
