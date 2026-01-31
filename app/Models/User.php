<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Notifications\Notifiable;
use GearboxSolutions\EloquentFileMaker\Database\Eloquent\FMModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Facades\MyUtilFacade;

use Illuminate\Database\Eloquent\Relations\hasOne;

class User extends FMModel implements
    MustVerifyEmailContract,
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // FileMaker field mapping
    protected $fieldMapping = [
        // FileMaker Relationship Field Mapping (related table field mapping)
        'users|USER_PHOTOS::thumbnail' => 'thumbnail',
        'users|USER_PHOTOS::id' => 'image_id',
    ];

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn(string|null $value) => MyUtilFacade::getContainerUrl($value),
        );
    }

    // Laravel Eloquent Relationship
    public function photo(): HasOne
    {
        return $this->hasOne(
            UserPhoto::class,
            'user_id',  // user_photos.user_id
            'id'        // users.id
        );
    }
}
