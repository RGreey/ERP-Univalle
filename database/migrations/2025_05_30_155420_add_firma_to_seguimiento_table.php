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
        Schema::table('seguimiento', function (Blueprint $table) {
            $table->text('firma_digital')->nullable();
            $table->integer('firma_size')->nullable();
            $table->integer('firma_pos')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seguimiento', function (Blueprint $table) {
            $table->dropColumn(['firma_digital', 'firma_size', 'firma_pos']);
        });
    }
};
