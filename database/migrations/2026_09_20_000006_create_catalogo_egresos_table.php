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
        Schema::create('catalogo_egresos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 150);
            $table->boolean('es_transferencia')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_egresos');
    }
};
