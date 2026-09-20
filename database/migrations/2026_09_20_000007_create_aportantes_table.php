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
        Schema::create('aportantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 150);
            $table->string('cui_dpi', 13)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aportantes');
    }
};
