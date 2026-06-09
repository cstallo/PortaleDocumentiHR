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
        Schema::create('dati_cedolino', function (Blueprint $table) {
    $table->id();

    // FK / contesto
    $table->foreignId('documento_id')->constrained('documenti')->cascadeOnDelete();
    $table->foreignId('azienda_id')->constrained('aziende')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

    // Anagrafica / periodo (dal parser)
    $table->char('codice_fiscale', 16)->nullable();
    $table->smallInteger('periodo_anno')->unsigned()->nullable();
    $table->tinyInteger('periodo_mese')->unsigned()->nullable();

    // Valori economici (nomi = campi JSON del contratto §2)
    $table->decimal('netto', 12, 2)->nullable();
    $table->decimal('imponibile_contributi_mese', 12, 2)->nullable();
    $table->decimal('imponibile_contributi_anno', 12, 2)->nullable();
    $table->decimal('irpef_pagata_mese', 12, 2)->nullable();
    $table->decimal('irpef_pagata_anno', 12, 2)->nullable();
    $table->decimal('ferie_residue', 8, 2)->nullable();      // può essere NEGATIVO
    $table->decimal('permessi_residui', 8, 2)->nullable();
    $table->decimal('retribuzione_di_fatto', 12, 2)->nullable();

    // Tracciabilità parsing
    $table->boolean('parsing_riuscito')->default(false);     // = response.success
    $table->json('parsing_note')->nullable();                // = response.warnings

    $table->timestamps();

    $table->index(['user_id', 'periodo_anno', 'periodo_mese'], 'idx_user_periodo');
    $table->index('azienda_id', 'idx_azienda');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dati_cedolino');
    }
};
