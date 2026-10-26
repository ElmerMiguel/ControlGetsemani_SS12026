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

class InformeDepartamentosSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Consolidado Departamentos';
    }

    public function array(): array
    {
        $c = $this->datos['cabecera'];
        $totales = $this->datos['totales'];

        $filas = [
            [$c['entidad']],
            [$c['titulo']],
            ['Período: '.$c['periodo_texto'].' | Moneda: '.$c['moneda']],
            ['Generado el: '.$c['generado_el']],
            [],
            [
                'Departamento',
                'Estado Información',
                'Saldo Inicial (Q)',
                '(+) Ingresos Ordinarios (Q)',
                '(+) Transf. Recibidas (Q)',
                '(=) Total Ingresos (Q)',
                '(-) Egresos Ordinarios (Q)',
                '(-) Transf. Enviadas (Q)',
                '(=) Total Egresos (Q)',
                '(=) Saldo Final (Q)',
            ],
        ];

        foreach ($this->datos['departamentos'] as $depto) {
            if (! $depto['tiene_informacion']) {
                $filas[] = [
                    $depto['departamento'],
                    $depto['mensaje'],
                    '—', '—', '—', '—', '—', '—', '—', '—',
                ];
            } else {
                $filas[] = [
                    $depto['departamento'],
                    'Oficial ('.$depto['total_cortes'].' cortes)',
                    (float) $depto['saldo_inicial'],
                    (float) $depto['ingresos_ordinarios'],
                    (float) $depto['transferencias_recibidas'],
                    (float) $depto['total_ingresos'],
                    (float) $depto['egresos_ordinarios'],
                    (float) $depto['transferencias_enviadas'],
                    (float) $depto['total_egresos'],
                    (float) $depto['saldo_final'],
                ];
            }
        }

        // Fila de totales globales
        $filas[] = [];
        $filas[] = [
            'TOTAL CONSOLIDADO ASAMBLEA',
            $totales['departamentos_con_info'].'/'.$totales['departamentos_total'].' deptos. con información',
            (float) $totales['saldo_inicial'],
            (float) $totales['ingresos_ordinarios'],
            (float) $totales['transferencias_recibidas'],
            (float) $totales['total_ingresos'],
            (float) $totales['egresos_ordinarios'],
            (float) $totales['transferencias_enviadas'],
            (float) $totales['total_egresos'],
            (float) $totales['saldo_final'],
        ];

        return $filas;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');
        $sheet->mergeCells('A4:J4');

        $totalFilas = count($this->array());

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0284C7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $totalFilas => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                ],
            ],
        ];
    }
}
