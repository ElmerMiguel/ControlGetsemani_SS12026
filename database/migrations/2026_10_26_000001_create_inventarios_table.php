<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Implementa tabla inventarios de ARQUITECTURA_FASE3 §3 (RF-09).
     */
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('valor_estimado', 14, 2)->nullable();
            $table->string('estado', 10)->default('correcto')->index();
            $table->date('fecha_registro');
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
