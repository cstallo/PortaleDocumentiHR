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
        Schema::create('bot_query_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();            // chi chiede
            $table->foreignId('dipendente_user_id')->nullable()->constrained('users')->nullOnDelete(); // su chi
            $table->foreignId('azienda_id')->nullable()->constrained('aziende')->nullOnDelete();
            $table->text('domanda');
            $table->string('esito');                 // risolta | non_risolta | errore
            $table->string('campo')->nullable();
            $table->unsignedSmallInteger('periodo_anno')->nullable();
            $table->unsignedTinyInteger('periodo_mese')->nullable();
            $table->text('risposta')->nullable();
            $table->string('motivo')->nullable();    // es. "campo non in whitelist", "nessun dato", errore LLM
            $table->boolean('gestita')->default(false);
            $table->timestamps();

            $table->index(['esito', 'gestita']);     // per filtrare velocemente le non risolte da gestire
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_query_logs');
    }
};
