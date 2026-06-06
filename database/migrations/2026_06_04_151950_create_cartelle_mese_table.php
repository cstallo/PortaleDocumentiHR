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
    Schema::create('cartelle_mese', function (Blueprint $table) {
        $table->id();
        $table->foreignId('azienda_id')->constrained('aziende')->cascadeOnDelete();
        $table->smallInteger('anno')->unsigned();
        $table->tinyInteger('mese')->unsigned(); // 1-12
        $table->string('label', 20); // es. "05-maggio"
        $table->string('path_relativo', 500); // es. "alfa-srl/2025/05-maggio"
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
        $table->unique(['azienda_id', 'anno', 'mese']);
    });
}

public function down(): void
{
    Schema::dropIfExists('cartelle_mese');
}

};
