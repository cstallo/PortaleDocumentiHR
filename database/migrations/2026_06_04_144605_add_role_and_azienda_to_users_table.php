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
        $table->string('role')->default('dipendente')->after('email');
        // $table->foreignId('azienda_id')->nullable()->constrained('aziende')->nullOnDelete()->after('role');
        $table->foreignId('azienda_id')->nullable()->nullOnDelete()->after('role');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['role', 'azienda_id']);
    });
}
};
