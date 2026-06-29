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
    Schema::table('aziende', function (Blueprint $table) {
        // Contatto del Titolare per informativa ed esercizio diritti (§1 e §6)
        $table->string('email_contatto')->nullable()->after('indirizzo');
        // Fornitore IT / Responsabile del trattamento ex art. 28 (§5)
        $table->string('responsabile_trattamento')->nullable()->after('email_contatto');
        // Contatto del DPO, ove nominato (§7)
        $table->string('dpo_email')->nullable()->after('responsabile_trattamento');
    });
}

public function down(): void
{
    Schema::table('aziende', function (Blueprint $table) {
        $table->dropColumn(['email_contatto', 'responsabile_trattamento', 'dpo_email']);
    });
}

};
