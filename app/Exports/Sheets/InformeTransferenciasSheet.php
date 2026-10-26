<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InformeTransferenciasSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $datos
    ) {}

    public function title(): string
    {
        return 'Movimientos Internos';
    }

    public function array(): array
    {
        $c = $this->datos['cabecera'];
        $items = $this->datos['transferencias']['items'];

        $filas = [
            [$c['entidad']],
            ['Movimientos y Transferencias Internas entre Cajas (RN-11 / RN3-04)'],
            ['Período: '.$c['periodo_texto'].' | Moneda: '.$c['moneda']],
            ['Generado el: '.$c['generado_el']],
            [],
            [
                'Fecha',
                'Caja Origen',
                'Departamento Origen',
                'Caja Destino',
                'Departamento Destino',
                'Monto (Q)',
                'Concepto / Justificación',
                'Registrado por',
            ],
        ];

        if ($items->isEmpty()) {
            $filas[] = ['Sin transferencias registradas en este período.', '', '', '', '', '', '', ''];
        } else {
            foreach ($items as $t) {
                $filas[] = [
                    $t->fecha?->format('d/m/Y'),
                    $t->cajaOrigen?->nombre ?? '—',
                    $t->cajaOrigen?->departamento?->nombre ?? '—',
                    $t->cajaDestino?->nombre ?? '—',
                    $t->cajaDestino?->departamento?->nombre ?? '—',
                    (float) $t->monto,
                    $t->concepto,
                    $t->usuario?->name ?? '—',
                ];
            }
        }

        return $filas;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');

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
                    'startColor' => ['rgb' => '334155'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
