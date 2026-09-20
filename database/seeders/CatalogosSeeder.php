<?php

namespace Database\Seeders;

use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingresos = [
            ['codigo' => '001', 'nombre' => 'Diezmo', 'es_transferencia' => false],
            ['codigo' => '002', 'nombre' => 'Ofrenda', 'es_transferencia' => false],
            ['codigo' => '003', 'nombre' => 'Donación', 'es_transferencia' => false],
            ['codigo' => '004', 'nombre' => 'Actividad Especial', 'es_transferencia' => false],
            ['codigo' => '005', 'nombre' => 'Aniversario', 'es_transferencia' => false],
            ['codigo' => '006', 'nombre' => 'Retiro', 'es_transferencia' => false],
            ['codigo' => '007', 'nombre' => 'Fondo de Construcción', 'es_transferencia' => false],
            ['codigo' => '008', 'nombre' => 'Uso de Local', 'es_transferencia' => false],
            ['codigo' => '009', 'nombre' => 'Otros Ingresos', 'es_transferencia' => false],
            ['codigo' => '900', 'nombre' => 'Transferencia Recibida', 'es_transferencia' => true],
        ];

        foreach ($ingresos as $ingreso) {
            CatalogoIngreso::updateOrCreate(
                ['codigo' => $ingreso['codigo']],
                [
                    'nombre' => $ingreso['nombre'],
                    'es_transferencia' => $ingreso['es_transferencia'],
                    'activo' => true,
                ]
            );
        }

        $egresos = [
            ['codigo' => '001', 'nombre' => 'Viajes / Transporte', 'es_transferencia' => false],
            ['codigo' => '002', 'nombre' => 'Combustible', 'es_transferencia' => false],
            ['codigo' => '003', 'nombre' => 'Cocina General', 'es_transferencia' => false],
            ['codigo' => '004', 'nombre' => 'Refacciones', 'es_transferencia' => false],
            ['codigo' => '005', 'nombre' => 'Alimentos', 'es_transferencia' => false],
            ['codigo' => '006', 'nombre' => 'Energía Eléctrica', 'es_transferencia' => false],
            ['codigo' => '007', 'nombre' => 'Internet y Recargas', 'es_transferencia' => false],
            ['codigo' => '008', 'nombre' => 'Ministerio de Sonido', 'es_transferencia' => false],
            ['codigo' => '009', 'nombre' => 'Papelería y Útiles', 'es_transferencia' => false],
            ['codigo' => '010', 'nombre' => 'Ayuda Social', 'es_transferencia' => false],
            ['codigo' => '011', 'nombre' => 'Donación a Colegio', 'es_transferencia' => false],
            ['codigo' => '012', 'nombre' => 'Materiales de Construcción', 'es_transferencia' => false],
            ['codigo' => '013', 'nombre' => 'Equipo de Computación', 'es_transferencia' => false],
            ['codigo' => '014', 'nombre' => 'Mobiliario', 'es_transferencia' => false],
            ['codigo' => '015', 'nombre' => 'Ofrenda a Invitados', 'es_transferencia' => false],
            ['codigo' => '016', 'nombre' => 'Conserjería', 'es_transferencia' => false],
            ['codigo' => '017', 'nombre' => 'Capacitaciones', 'es_transferencia' => false],
            ['codigo' => '018', 'nombre' => 'Transmisiones', 'es_transferencia' => false],
            ['codigo' => '019', 'nombre' => 'Otros Gastos', 'es_transferencia' => false],
            ['codigo' => '020', 'nombre' => 'Ofrenda a Ministros Locales (sueldo, aguinaldo, indemnización)', 'es_transferencia' => false],
            ['codigo' => '021', 'nombre' => 'Corte de Caja (perito)', 'es_transferencia' => false],
            ['codigo' => '022', 'nombre' => 'Maíz y Leña', 'es_transferencia' => false],
            ['codigo' => '023', 'nombre' => 'Agua Potable', 'es_transferencia' => false],
            ['codigo' => '024', 'nombre' => 'Mantenimiento de Inmueble y Casa Pastoral', 'es_transferencia' => false],
            ['codigo' => '025', 'nombre' => 'Materiales y Herramientas Eléctricas', 'es_transferencia' => false],
            ['codigo' => '026', 'nombre' => 'Equipos y Accesorios Audiovisuales', 'es_transferencia' => false],
            ['codigo' => '027', 'nombre' => 'Viáticos y Dieta de Consejo', 'es_transferencia' => false],
            ['codigo' => '028', 'nombre' => 'Sesiones, Plan de Trabajo y Sesión Plenaria', 'es_transferencia' => false],
            ['codigo' => '029', 'nombre' => 'Bienvenida, Despedida y Traslado de Ministros', 'es_transferencia' => false],
            ['codigo' => '030', 'nombre' => 'Actividades Especiales (Aniversario, Retiro, Encuentro, Día del Pastor, Convivio)', 'es_transferencia' => false],
            ['codigo' => '031', 'nombre' => 'Ofrenda a Misioneros y Congregaciones', 'es_transferencia' => false],
            ['codigo' => '032', 'nombre' => 'Reparación de Vehículo', 'es_transferencia' => false],
            ['codigo' => '033', 'nombre' => 'Higiene y Limpieza', 'es_transferencia' => false],
            ['codigo' => '034', 'nombre' => 'Multas', 'es_transferencia' => false],
            ['codigo' => '035', 'nombre' => 'Oración y Ancianos', 'es_transferencia' => false],
            ['codigo' => '036', 'nombre' => 'Aporte a Ministerios (Infantil, Social, Ujieres, Comunión)', 'es_transferencia' => false],
            ['codigo' => '900', 'nombre' => 'Transferencia Enviada', 'es_transferencia' => true],
        ];

        foreach ($egresos as $egreso) {
            CatalogoEgreso::updateOrCreate(
                ['codigo' => $egreso['codigo']],
                [
                    'nombre' => $egreso['nombre'],
                    'es_transferencia' => $egreso['es_transferencia'],
                    'activo' => true,
                ]
            );
        }
    }
}
