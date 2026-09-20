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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->string('medio', 10)->default('efectivo');
            $table->decimal('saldo_apertura', 14, 2)->default(0);
            $table->date('fecha_apertura');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['departamento_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
