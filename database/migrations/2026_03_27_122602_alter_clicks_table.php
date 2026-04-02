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
            $table->string('country_code', 2)->nullable()->after('ip');
            $table->float('lat', 10, 6)->nullable()->after('country_code');
            $table->float('lng', 10, 6)->nullable()->after('lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropColumn('country_code');
            $table->dropColumn('lat');
            $table->dropColumn('lng');
        });
    }
};

