<?php

namespace App\Models;

use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class FishingTrip extends FMModel
{
    use HasFactory;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'trip_date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FishingTripPhoto::class, 'fishing_trip_id', 'id')
            ->orderBy('sort_order');
    }

    protected function tripDateLabel(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->formatDateAttribute($attributes['trip_date'] ?? null, 'Y-m-d'),
        );
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->formatDateAttribute($attributes['start_at'] ?? null, 'H:i'),
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->formatDateAttribute($attributes['end_at'] ?? null, 'H:i'),
        );
    }

    protected function startAtInput(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->formatDateAttribute($attributes['start_at'] ?? null, 'Y-m-d\TH:i'),
        );
    }

    protected function endAtInput(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->formatDateAttribute($attributes['end_at'] ?? null, 'Y-m-d\TH:i'),
        );
    }

    private function formatDateAttribute(mixed $value, string $format): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format($format);
    }
}
