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
        Schema::table('dati_cedolino', function (Blueprint $table) {
    $table->unique(
        ['azienda_id', 'codice_fiscale', 'periodo_anno', 'periodo_mese'],
        'uq_cedolino'
    );
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dati_cedolino', function (Blueprint $table) {
    $table->dropUnique('uq_cedolino');
});

    }
};
