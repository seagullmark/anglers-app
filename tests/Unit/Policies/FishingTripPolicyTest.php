<?php

namespace Tests\Unit\Policies;

use App\Models\FishingTrip;
use App\Models\User;
use App\Policies\FishingTripPolicy;
use PHPUnit\Framework\TestCase;

class FishingTripPolicyTest extends TestCase
{
    public function test_owner_can_view_update_and_delete_trip(): void
    {
        $user = new User();
        $user->id = 'user-1';

        $trip = new FishingTrip();
        $trip->user_id = 'user-1';

        $policy = new FishingTripPolicy();

        $this->assertTrue($policy->view($user, $trip));
        $this->assertTrue($policy->update($user, $trip));
        $this->assertTrue($policy->delete($user, $trip));
    }

    public function test_non_owner_cannot_view_update_or_delete_trip(): void
    {
        $user = new User();
        $user->id = 'user-2';

        $trip = new FishingTrip();
        $trip->user_id = 'user-1';

        $policy = new FishingTripPolicy();

        $this->assertFalse($policy->view($user, $trip));
        $this->assertFalse($policy->update($user, $trip));
        $this->assertFalse($policy->delete($user, $trip));
    }
}
