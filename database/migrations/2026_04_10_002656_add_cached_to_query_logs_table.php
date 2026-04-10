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
        Schema::table('query_logs', function (Blueprint $table) {
            $table->boolean('cached')->default(false)->after('execution_time_ms');
        });
    }

    public function down(): void
    {
        Schema::table('query_logs', function (Blueprint $table) {
            $table->dropColumn('cached');
        });
    }
};
