<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campo correo electrónico a aportantes para envío digital de comprobantes.
     */
    public function up(): void
    {
        Schema::table('aportantes', function (Blueprint $table) {
            $table->string('correo', 150)->nullable()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aportantes', function (Blueprint $table) {
            $table->dropColumn('correo');
        });
    }
};
