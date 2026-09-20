<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->restrictOnDelete();
            $table->date('fecha');
            $table->foreignId('cuenta_ingreso_id')->constrained('catalogo_ingresos')->restrictOnDelete();
            $table->decimal('monto', 14, 2);
            $table->string('recibo', 100)->nullable();
            $table->foreignId('aportante_id')->nullable()->constrained('aportantes')->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('transferencia_id')->nullable()->constrained('transferencias')->restrictOnDelete();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('motivo_anulacion', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['caja_id', 'fecha']);
            $table->index('cuenta_ingreso_id');
            $table->index('transferencia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
