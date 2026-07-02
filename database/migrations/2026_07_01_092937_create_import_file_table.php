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
        Schema::create('import_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained('import_log')->cascadeOnDelete();
            $table->string('nome_file');
            $table->char('codice_fiscale', 16)->nullable()->index();
            $table->string('esito_elaborazione')->index(); // importato | nome_non_conforme | duplicato_saltato | cf_non_trovato
            $table->foreignId('documento_id')->nullable()->constrained('documenti')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_destinatario')->nullable();
            $table->string('notifica_id')->nullable()->index();
            $table->string('nota_errore')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_file');
    }
};
