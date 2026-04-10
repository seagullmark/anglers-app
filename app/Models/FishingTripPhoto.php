<?php

namespace App\Models;

use App\Facades\MyUtilFacade;
use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishingTripPhoto extends FMModel
{
    use HasFactory;

    protected $hidden = [
        'image',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'id' => 'string',
        'fishing_trip_id' => 'string',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fishingTrip(): BelongsTo
    {
        return $this->belongsTo(FishingTrip::class, 'fishing_trip_id', 'id');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => MyUtilFacade::getContainerUrl($this->getRawOriginal('image') ?? $this->getAttributeFromArray('image')),
        );
    }
}
