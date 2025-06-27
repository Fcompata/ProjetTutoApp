<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyEstimation extends Model
{
    protected $fillable = [
        'name',
        'trajectory_length',
        'average_speed',
        'obstacle_count',
        'detection_threshold',
        'measurement_frequency',
        'backup_time',
        'lateral_measurement_time',
        'rotation_time',
        'battery_capacity',
    ];

    public function components()
    {
        return $this->hasMany(Component::class, 'estimation_id');
    }
}