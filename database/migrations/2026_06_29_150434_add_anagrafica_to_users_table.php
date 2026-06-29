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
    Schema::table('users', function (Blueprint $table) {
        // Nome scomposto (name resta come "COGNOME Nome" per login/mail/viste)
        $table->string('cognome')->nullable()->after('name');
        $table->string('nome')->nullable()->after('cognome');

        // Dati gestionale / organizzazione
        $table->string('matricola')->nullable()->after('codice_fiscale');
        $table->string('sede')->nullable()->after('matricola');

        // Anagrafica personale (GDPR: dati personali)
        $table->char('sesso', 1)->nullable()->after('sede');     // 'F' / 'M'
        $table->string('luogo_nascita')->nullable()->after('sesso');
        $table->date('data_nascita')->nullable()->after('luogo_nascita');

        // Rapporto di lavoro
        $table->date('data_assunzione')->nullable()->after('data_nascita');
        $table->date('data_licenziamento')->nullable()->after('data_assunzione');
        $table->date('scadenza_contratto')->nullable()->after('data_licenziamento');
    });
}

    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'cognome', 'nome', 'matricola', 'sede', 'sesso',
            'luogo_nascita', 'data_nascita',
            'data_assunzione', 'data_licenziamento', 'scadenza_contratto',
        ]);
    });
}
};
