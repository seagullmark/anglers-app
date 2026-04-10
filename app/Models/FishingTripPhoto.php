<?php

namespace App\Models;

use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishingTripPhoto extends FMModel
{
    use HasFactory;

    protected $casts = [
        'id' => 'string',
        'fishing_trip_id' => 'string',
    ];

    public function fishingTrip(): BelongsTo
    {
        return $this->belongsTo(FishingTrip::class, 'fishing_trip_id', 'id');
    }
}
