<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Implementa la matriz de roles y permisos de ARQUITECTURA_FASE2 §7.1 de forma idempotente.
     */
    public function run(): void
    {
        // Limpiar caché previa de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Lista de permisos de ARQUITECTURA §7.1
        $permisos = [
            'usuarios.gestionar',
            'departamentos.gestionar',
            'cajas.gestionar',
            'catalogos.gestionar',
            'catalogos.ver',
            'aportantes.gestionar',
            'ingresos.ver',
            'ingresos.crear',
            'ingresos.editar',
            'ingresos.anular',
            'egresos.ver',
            'egresos.crear',
            'egresos.editar',
            'egresos.anular',
            'transferencias.crear',
            'cortes.solicitar',
            'cortes.aprobar',
            'cortes.reabrir',
            'reportes.ver',
            'reportes.exportar',
            'bitacora.ver',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Crear roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $tesorero = Role::firstOrCreate(['name' => 'tesorero', 'guard_name' => 'web']);

        // Asignación según matriz de ARQUITECTURA §7.1
        // Admin tiene todos los permisos
        $admin->syncPermissions(Permission::all());

        // Tesorero tiene permisos operativos para sus cajas y catálogos/aportantes
        $tesorero->syncPermissions([
            'catalogos.ver',
            'aportantes.gestionar',
            'ingresos.ver',
            'ingresos.crear',
            'ingresos.editar',
            'ingresos.anular',
            'egresos.ver',
            'egresos.crear',
            'egresos.editar',
            'egresos.anular',
            'cortes.solicitar',
            'reportes.ver',
            'reportes.exportar',
        ]);

        // Limpiar caché al finalizar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
