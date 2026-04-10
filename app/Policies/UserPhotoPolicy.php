<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPhoto;

class UserPhotoPolicy
{
    public function view(User $user, UserPhoto $userPhoto): bool
    {
        return (string) $userPhoto->user_id === (string) $user->id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->id);
    }

    public function update(User $user, UserPhoto $userPhoto): bool
    {
        return (string) $userPhoto->user_id === (string) $user->id;
    }
}
