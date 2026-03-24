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
        Schema::dropIfExists('clicks');
        Schema::create('clicks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('short_url_id', 26);
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at');
            $table->foreign('short_url_id')
                ->references('id')
                ->on('short_urls')
                ->cascadeOnDelete();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_url_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamps();
        });
    }
};
