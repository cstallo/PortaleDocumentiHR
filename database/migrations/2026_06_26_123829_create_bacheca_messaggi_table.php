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
        Schema::create('bacheca_messaggi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autore_id')
                ->constrined('user')
                ->cascadeOnDelete();
            $table->string('titolo');
            $table->text('corpo');
            $table->boolean('pinned')->default(false);
            $table->timestamp('pubblicato_il')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bacheca_messaggi');
    }
};
