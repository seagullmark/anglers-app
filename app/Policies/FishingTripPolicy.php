<?php

namespace App\Policies;

use App\Models\FishingTrip;
use App\Models\User;

class FishingTripPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->id);
    }

    public function view(User $user, FishingTrip $fishingTrip): bool
    {
        return ! empty($user->id);
    }

    public function create(User $user): bool
    {
        return ! empty($user->id);
    }

    public function update(User $user, FishingTrip $fishingTrip): bool
    {
        return (string) $fishingTrip->user_id === (string) $user->id;
    }

    public function delete(User $user, FishingTrip $fishingTrip): bool
    {
        return (string) $fishingTrip->user_id === (string) $user->id;
    }
}
