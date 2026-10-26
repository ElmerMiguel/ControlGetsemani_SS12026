<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Implementa tabla comprobantes_donacion de ARQUITECTURA_FASE3 §3 (RF-07 / RF-14).
     */
    public function up(): void
    {
        Schema::create('comprobantes_donacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingreso_id')->unique()->constrained('ingresos')->cascadeOnDelete();
            $table->foreignId('aportante_id')->constrained('aportantes');
            $table->string('numero', 30)->unique(); // Formato COMP-000001
            $table->timestamp('generado_en');
            $table->foreignId('generado_por')->constrained('users');
            $table->timestamp('enviado_en')->nullable();
            $table->string('ruta_pdf', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes_donacion');
    }
};
