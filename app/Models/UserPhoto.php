<?php

namespace App\Models;

use App\Facades\MyUtilFacade;
use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Casts\Attribute;

class UserPhoto extends FMModel
{
    use HasFactory;

    protected $casts = [
        'id' => 'string',
    ];

    // protected function thumbnail(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn(string|null $value) => MyUtilFacade::getContainerUrl($value),
    //     );
    // }
}
