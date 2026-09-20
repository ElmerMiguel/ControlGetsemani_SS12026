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
        Schema::create('cortes_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->restrictOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('estado', 12)->default('pendiente');
            $table->decimal('saldo_inicial', 14, 2);
            $table->decimal('total_ingresos', 14, 2);
            $table->decimal('total_egresos', 14, 2);
            $table->decimal('saldo_final', 14, 2);
            $table->foreignId('solicitado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('solicitado_at');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['caja_id', 'periodo_inicio', 'periodo_fin']);
            $table->index(['caja_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cortes_caja');
    }
};
