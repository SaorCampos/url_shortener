<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE short_urls ALTER COLUMN id DROP DEFAULT');
        Schema::table('short_urls', function ($table) {
            $table->timestamp('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE short_urls ALTER COLUMN id SET DEFAULT nextval('short_urls_id_seq')"
        );
        Schema::table('short_urls', function ($table) {
            $table->dropColumn('expires_at');
        });
    }
};
