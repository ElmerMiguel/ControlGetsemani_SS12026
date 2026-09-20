<?php

namespace Database\Seeders;

use App\Enums\MedioCaja;
use App\Enums\TipoDepartamento;
use App\Models\Caja;
use App\Models\Departamento;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EstructuraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fechaApertura = Carbon::create(now()->year, 1, 1)->toDateString();

        $estructura = [
            [
                'departamento' => [
                    'nombre' => 'Consejo Local',
                    'tipo' => TipoDepartamento::Consejo,
                    'descripcion' => 'Órgano central directivo y administrativo de la congregación local.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Diezmo Consejo Local',
                        'codigo' => 'CAJA-CL-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 16498.00,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Juvenil "Dios es Amor"',
                    'tipo' => TipoDepartamento::Consejo,
                    'descripcion' => 'Departamento de jóvenes de la iglesia.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Ofrendas Juvenil',
                        'codigo' => 'CAJA-JUV-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 933.50,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Ministerio Infantil "Jesús Defensor de los Niños"',
                    'tipo' => TipoDepartamento::Comite,
                    'descripcion' => 'Ministerio de educación y alabanza infantil.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Infantil – Música',
                        'codigo' => 'CAJA-INF-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 7168.25,
                    ],
                    [
                        'nombre' => 'Infantil – General',
                        'codigo' => 'CAJA-INF-02',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 0.00,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Junta Jurídica',
                    'tipo' => TipoDepartamento::Junta,
                    'descripcion' => 'Junta administradora jurídica central receptora del diezmo de diezmo.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Caja Jurídica',
                        'codigo' => 'CAJA-JUR-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 25676.75,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Comité de Construcción',
                    'tipo' => TipoDepartamento::Comite,
                    'descripcion' => 'Comité encargado de proyectos de infraestructura y mantenimiento.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Caja 1',
                        'codigo' => 'CAJA-CONST-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 0.00,
                    ],
                    [
                        'nombre' => 'Caja 2',
                        'codigo' => 'CAJA-CONST-02',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 0.00,
                    ],
                    [
                        'nombre' => 'Control de Banco',
                        'codigo' => 'CAJA-CONST-03',
                        'medio' => MedioCaja::Banco,
                        'saldo_apertura' => 0.00,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Congregación Chamwakax',
                    'tipo' => TipoDepartamento::Congregacion,
                    'descripcion' => 'Congregación y misión filial en Chamwakax.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Diezmo "Cristo es la Antorcha"',
                        'codigo' => 'CAJA-CHAM-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 101552.00,
                    ],
                    [
                        'nombre' => 'Ofrenda "Cristo es la Antorcha"',
                        'codigo' => 'CAJA-CHAM-02',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 503.50,
                    ],
                    [
                        'nombre' => 'Femenil "Loida y Eunice"',
                        'codigo' => 'CAJA-CHAM-03',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 4248.00,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Consejo Femenil',
                    'tipo' => TipoDepartamento::Consejo,
                    'descripcion' => 'Sociedad y consejo de damas de la congregación.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Caja General',
                        'codigo' => 'CAJA-FEM-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 0.00,
                    ],
                ],
            ],
            [
                'departamento' => [
                    'nombre' => 'Radio Estéreo Getsemaní',
                    'tipo' => TipoDepartamento::Comite,
                    'descripcion' => 'Comité de ministerio radial de la iglesia.',
                ],
                'cajas' => [
                    [
                        'nombre' => 'Caja General',
                        'codigo' => 'CAJA-RAD-01',
                        'medio' => MedioCaja::Efectivo,
                        'saldo_apertura' => 0.00,
                    ],
                ],
            ],
        ];

        foreach ($estructura as $item) {
            $depto = Departamento::updateOrCreate(
                ['nombre' => $item['departamento']['nombre']],
                [
                    'tipo' => $item['departamento']['tipo'],
                    'descripcion' => $item['departamento']['descripcion'],
                    'activo' => true,
                ]
            );

            foreach ($item['cajas'] as $caja) {
                Caja::updateOrCreate(
                    [
                        'departamento_id' => $depto->id,
                        'nombre' => $caja['nombre'],
                    ],
                    [
                        'codigo' => $caja['codigo'],
                        'medio' => $caja['medio'],
                        'saldo_apertura' => $caja['saldo_apertura'],
                        'fecha_apertura' => $fechaApertura,
                        'activa' => true,
                    ]
                );
            }
        }
    }
}
