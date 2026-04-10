<?php

namespace App\Models;

use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserPhoto extends FMModel
{
    use HasFactory;

    protected $hidden = [
        'photo',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
    ];

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->id)
                ? route('user-photos.image', $this->id)
                : null,
        );
    }
}
