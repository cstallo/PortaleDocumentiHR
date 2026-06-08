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
    Schema::create('documenti', function (Blueprint $table) {
        $table->id();
        $table->string('nome_file');
        $table->string('path_storage', 1000);
        $table->char('codice_fiscale', 16);
        $table->foreignId('azienda_id')->constrained('aziende')->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('cartella_mese_id')->constrained('cartelle_mese')->cascadeOnDelete();
        // $table->foreignId('import_log_id')->nullable()->constrained('import_log')->nullOnDelete();
        $table->foreignId('import_log_id')->nullable()->nullOnDelete();
        $table->boolean('utente_non_trovato')->default(false);
        $table->timestamp('scaricato_il')->nullable();
        $table->timestamps();
        $table->index(['azienda_id', 'codice_fiscale']);
        $table->index('user_id');
        
    });
}

public function down(): void
{
    Schema::dropIfExists('documenti');
}

};
