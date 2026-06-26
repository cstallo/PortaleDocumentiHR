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
        Schema::create('bacheca_destinatari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('messaggio_id')
                ->constrained('bacheca_messaggi')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('letto_il')->nullable();
            $table->boolean('notifica_inviata')
                ->default(false);
            $table->timestamps();

            $table->unique(['messaggio_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bacheca_destinatari');
    }
};
