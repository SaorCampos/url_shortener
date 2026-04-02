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
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropForeign(['short_url_id']);
        });
        Schema::table('short_urls', function (Blueprint $table) {
            $table->char('ulid_new', 26)->nullable()->before('original_url');
        });
        // Opcional: Popular os ULIDs para registros existentes
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropPrimary('short_urls_pkey');
            $table->dropColumn('id');
        });
        Schema::table('short_urls', function (Blueprint $table) {
            $table->renameColumn('ulid_new', 'id');
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropPrimary('short_urls_pkey');
            $table->dropColumn('id');
        });
        Schema::table('short_urls', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('clicks', function (Blueprint $table) {
            $table->foreignId('short_url_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
