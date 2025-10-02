<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidio_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->text('texto');
            $table->timestamps();

            $table->index(['user_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_observaciones');
    }
};