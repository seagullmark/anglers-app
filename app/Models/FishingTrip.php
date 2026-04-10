<?php

namespace App\Models;

use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishingTrip extends FMModel
{
    use HasFactory;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FishingTripPhoto::class, 'fishing_trip_id', 'id');
    }
}
