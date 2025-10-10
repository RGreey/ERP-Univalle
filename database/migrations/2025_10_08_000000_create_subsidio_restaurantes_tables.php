<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidio_restaurantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 120);
            $table->timestamps();
        });

        Schema::create('subsidio_restaurante_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('subsidio_restaurantes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['restaurante_id','user_id'], 'rest_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_restaurante_user');
        Schema::dropIfExists('subsidio_restaurantes');
    }
};