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
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 40);
            $table->string('tabla_afectada', 100)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('descripcion', 255);
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('fecha_hora')->useCurrent();

            $table->index(['tabla_afectada', 'registro_id']);
            $table->index(['usuario_id', 'fecha_hora']);
            $table->index('fecha_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
