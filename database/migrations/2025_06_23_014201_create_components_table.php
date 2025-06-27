<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimation_id')->constrained('energy_estimations')->onDelete('cascade');
            $table->string('name');
            $table->decimal('consumption_rate', 10, 4);
            $table->string('unit');
            $table->string('formula');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};