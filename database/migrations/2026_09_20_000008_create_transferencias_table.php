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
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_origen_id')->constrained('cajas')->restrictOnDelete();
            $table->foreignId('caja_destino_id')->constrained('cajas')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('monto', 14, 2);
            $table->string('concepto', 255);
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('motivo_anulacion', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
