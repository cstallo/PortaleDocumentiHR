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
    Schema::create('import_log', function (Blueprint $table) {
        $table->id();
        $table->foreignId('azienda_id')->constrained('aziende')->cascadeOnDelete();
        $table->string('zip_originale');
        $table->string('zip_path_temp', 1000);
        $table->foreignId('cartella_mese_id')->constrained('cartelle_mese')->cascadeOnDelete();
        $table->foreignId('admin_id')->constrained('users');
        $table->enum('stato', ['in_coda', 'in_elaborazione', 'completato', 'errore'])->default('in_coda');
        $table->integer('tot_file_zip')->default(0);
        $table->integer('file_elaborati')->default(0);
        $table->integer('file_errore_cf')->default(0);
        $table->integer('file_nome_non_valido')->default(0);
        $table->integer('file_duplicati')->default(0);
        $table->json('dettaglio_errori')->nullable();
        $table->timestamp('completato_il')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('import_log');
}

};
