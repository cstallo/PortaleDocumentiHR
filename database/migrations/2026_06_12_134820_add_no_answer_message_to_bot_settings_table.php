<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bot_settings', 'no_answer_message')) {
            Schema::table('bot_settings', function (Blueprint $table) {
                $table->text('no_answer_message')->nullable()->after('endpoint');
            });
        }

        DB::table('bot_settings')->whereNull('no_answer_message')->update([
            'no_answer_message' => 'Non ho una risposta a questa richiesta. La segnalo agli amministratori, che provvederanno quanto prima a fornirti supporto in merito.',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn('no_answer_message');
        });
    }
};
