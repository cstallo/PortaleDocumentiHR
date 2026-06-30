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
    Schema::create('email_logs', function (Blueprint $table) {
        $table->id();
        $table->string('notifica_id')->nullable()->index();   // UUID notifica (correlazione retry)
        $table->string('destinatario');
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('azienda_id')->nullable()->constrained('aziende')->nullOnDelete();
        $table->string('tipo');                                 // benvenuto_dipendente | benvenuto_hr | nuovo_documento
        $table->string('oggetto')->nullable();
        $table->string('stato');                                // inviata | fallita
        $table->text('errore')->nullable();
        $table->unsignedInteger('tentativi')->default(0);
        $table->timestamp('inviata_il')->nullable();
        $table->timestamps();

        $table->index(['azienda_id', 'stato']);
        $table->index('created_at');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
