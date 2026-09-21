<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EgresosSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Egresos';
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado principal
        $rows[] = ['EGRESOS ORDINARIOS POR CUENTA'];
        $rows[] = ['Código', 'Cuenta Contable', 'Monto (Q)', 'Porcentaje (%)'];

        foreach ($this->datos['egresos_por_cuenta'] as $item) {
            $rows[] = [
                $item['codigo'],
                $item['nombre'],
                (float) $item['total'],
                (float) $item['porcentaje'],
            ];
        }

        $rows[] = [
            'TOTAL',
            'TOTAL EGRESOS ORDINARIOS',
            (float) $this->datos['total_egresos_cuentas'],
            100.00,
        ];

        // Transferencias si existen
        if (! empty($this->datos['transferencias']['enviadas'])) {
            $rows[] = [];
            $rows[] = ['TRANSFERENCIAS ENVIADAS (INTERNAS)'];
            $rows[] = ['Fecha', 'Caja de Destino', 'Concepto / Motivo', 'Monto (Q)'];

            foreach ($this->datos['transferencias']['enviadas'] as $t) {
                $rows[] = [
                    $t['fecha'],
                    $t['caja_destino'],
                    $t['concepto'],
                    (float) $t['monto'],
                ];
            }

            $rows[] = [
                'TOTAL',
                'TOTAL TRANSFERENCIAS ENVIADAS',
                '',
                (float) $this->datos['transferencias']['total_enviadas'],
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $totalCuentas = count($this->datos['egresos_por_cuenta']);
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
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E11D48']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $filaTotalOrdinarios => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']],
            ],
        ];

        if (! empty($this->datos['transferencias']['enviadas'])) {
            $filaTituloTransf = $filaTotalOrdinarios + 2;
            $filaHeaderTransf = $filaTituloTransf + 1;
            $filaTotalTransf = $filaHeaderTransf + count($this->datos['transferencias']['enviadas']) + 1;

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
