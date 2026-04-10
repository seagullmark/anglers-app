<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\DestroyFishingTripRequest;
use App\Http\Requests\StoreFishingTripRequest;
use App\Http\Requests\UpdateFishingTripRequest;
use PHPUnit\Framework\TestCase;

class FishingTripRequestTest extends TestCase
{
    public function test_store_request_has_trip_and_photo_rules(): void
    {
        $rules = (new StoreFishingTripRequest())->rules();

        $this->assertArrayHasKey('trip_date', $rules);
        $this->assertArrayHasKey('start_at', $rules);
        $this->assertArrayHasKey('end_at', $rules);
        $this->assertArrayHasKey('photos', $rules);
        $this->assertArrayHasKey('photos.*', $rules);
    }

    public function test_update_request_requires_mod_id_and_remove_photo_ids(): void
    {
        $rules = (new UpdateFishingTripRequest())->rules();

        $this->assertArrayHasKey('mod_id', $rules);
        $this->assertArrayHasKey('remove_photo_ids', $rules);
        $this->assertArrayHasKey('remove_photo_ids.*', $rules);
    }

    public function test_destroy_request_requires_mod_id(): void
    {
        $rules = (new DestroyFishingTripRequest())->rules();

        $this->assertSame(['required', 'string'], $rules['mod_id']);
    }
}
