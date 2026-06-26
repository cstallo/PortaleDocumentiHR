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
        Schema::table('documenti', function (Blueprint $table) {
            $table->text('descrizione')->nullable()->after('tipo');
            $table->date('data_documento')->nullable()->after('descrizione');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documenti', function (Blueprint $table) {
            $table->dropColumn(['descrizione', 'data_documento']);
        });

    }
};
