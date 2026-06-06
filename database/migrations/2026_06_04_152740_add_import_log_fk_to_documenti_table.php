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
        $table->foreign('import_log_id')->references('id')->on('import_log')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('documenti', function (Blueprint $table) {
        $table->dropForeign(['import_log_id']);
    });
}

};
