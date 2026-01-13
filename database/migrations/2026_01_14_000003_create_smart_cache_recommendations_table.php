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
        Schema::create('smart_cache_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('query_hash', 32)->index();
            $table->text('query');
            $table->string('priority'); // high, medium, low
            $table->integer('suggested_ttl');
            $table->string('reason');
            $table->float('potential_savings');
            $table->string('status')->default('pending'); // pending, approved, rejected, applied
            $table->boolean('auto_applied')->default(false);
            $table->text('applied_config')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_cache_recommendations');
    }
};
