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
        Schema::create('smart_cache_query_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('query_hash', 32)->unique();
            $table->text('query');
            $table->bigInteger('execution_count')->default(0);
            $table->decimal('total_time', 15, 2)->default(0);
            $table->decimal('avg_time', 10, 2)->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();

            $table->index('avg_time');
            $table->index('execution_count');
            $table->index('last_executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_cache_query_analyses');
    }
};
