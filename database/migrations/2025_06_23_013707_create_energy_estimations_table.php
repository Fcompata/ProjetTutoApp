<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_estimations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('trajectory_length', 10, 4);
            $table->decimal('average_speed', 10, 4);
            $table->integer('obstacle_count');
            $table->decimal('detection_threshold', 10, 4);
            $table->integer('measurement_frequency');
            $table->decimal('backup_time', 10, 4);
            $table->decimal('lateral_measurement_time', 10, 4);
            $table->decimal('rotation_time', 10, 4);
            $table->decimal('battery_capacity', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_estimations');
    }
};