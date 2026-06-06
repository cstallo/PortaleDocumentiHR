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
    Schema::create('hr_azienda', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('azienda_id')->constrained('aziende')->cascadeOnDelete();
        $table->timestamps();
        $table->unique(['user_id', 'azienda_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('hr_azienda');
}

};
