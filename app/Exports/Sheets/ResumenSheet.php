<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $c = $this->datos['cabecera'];
        $r = $this->datos['resumen'];

        return [
            [$c['entidad']],
            ['Reporte Financiero de Caja: '.$c['caja'].' ('.$c['caja_codigo'].')'],
            ['Departamento: '.$c['departamento']],
            ['Período: '.$c['periodo_texto'].' | Moneda: '.$c['moneda']],
            ['Generado el: '.$c['generado_el']],
            [],
            ['Concepto Contable', 'Monto (Q)'],
            ['Saldo Inicial', (float) $r['saldo_inicial']],
            ['(+) Ingresos Ordinarios', (float) $r['ingresos_ordinarios']],
            ['(+) Transferencias Recibidas', (float) $r['transferencias_recibidas']],
            ['(=) Total Ingresos del Período', (float) $r['total_ingresos']],
            ['(-) Egresos Ordinarios', (float) $r['egresos_ordinarios']],
            ['(-) Transferencias Enviadas', (float) $r['transferencias_enviadas']],
            ['(=) Total Egresos del Período', (float) $r['total_egresos']],
            ['(=) Flujo Neto del Período', (float) $r['neto_periodo']],
            ['(=) Saldo Final al Cierre', (float) $r['saldo_final']],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A3:B3');
        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');

        // Formato moneda para valores numéricos
        $sheet->getStyle('B8:B16')->getNumberFormat()->setFormatCode('"Q "#,##0.00');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E293B']]],
            2 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0284C7']]],
            3 => ['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']]],
            4 => ['font' => ['size' => 10, 'color' => ['rgb' => '64748B']]],
            5 => ['font' => ['size' => 9, 'color' => ['rgb' => '94A3B8']]],
            7 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            11 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]],
            14 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']]],
            15 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]],
            16 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}
