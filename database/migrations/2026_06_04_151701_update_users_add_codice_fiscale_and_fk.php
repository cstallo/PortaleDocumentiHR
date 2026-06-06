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
        $table->char('codice_fiscale', 16)->nullable()->after('azienda_id');
        $table->enum('role', ['super_admin', 'hr', 'dipendente'])
              ->default('dipendente')
              ->change();
        $table->foreign('azienda_id')->references('id')->on('aziende')->nullOnDelete();
        $table->index('azienda_id');
        $table->unique(['codice_fiscale', 'azienda_id']);
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['azienda_id']);
        $table->dropIndex(['azienda_id']);
        $table->dropUnique(['codice_fiscale', 'azienda_id']);
        $table->dropColumn('codice_fiscale');
        $table->string('role')->default('dipendente')->change();
    });
}

};
