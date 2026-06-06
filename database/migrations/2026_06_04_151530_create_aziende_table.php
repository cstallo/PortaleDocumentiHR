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
    Schema::create('aziende', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('slug', 100)->unique();
        $table->string('codice_fiscale', 20)->nullable()->unique();
        $table->string('partita_iva', 20)->nullable()->unique();
        $table->string('indirizzo', 500)->nullable();
        $table->boolean('attiva')->default(true);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('aziende');
}

};
