<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\UserPhoto;
use App\Policies\UserPhotoPolicy;
use PHPUnit\Framework\TestCase;

class UserPhotoPolicyTest extends TestCase
{
    public function test_owner_can_view_and_update_photo(): void
    {
        $user = new User();
        $user->id = 'user-1';

        $photo = new UserPhoto();
        $photo->user_id = 'user-1';

        $policy = new UserPhotoPolicy();

        $this->assertTrue($policy->view($user, $photo));
        $this->assertTrue($policy->update($user, $photo));
    }

    public function test_non_owner_cannot_view_or_update_photo(): void
    {
        $user = new User();
        $user->id = 'user-2';

        $photo = new UserPhoto();
        $photo->user_id = 'user-1';

        $policy = new UserPhotoPolicy();

        $this->assertFalse($policy->view($user, $photo));
        $this->assertFalse($policy->update($user, $photo));
    }
}
