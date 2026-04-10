<?php

namespace Tests\Unit\Support;

use App\Support\FishingTripSampleFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class FishingTripSampleFactoryTest extends TestCase
{
    public function test_it_builds_trip_attributes(): void
    {
        $factory = new FishingTripSampleFactory();
        $attributes = $factory->makeTripAttributes('user-1', 2, Carbon::parse('2026-04-10 12:00:00'));

        $this->assertTrue(Str::isUuid($attributes['id']));
        $this->assertSame('user-1', $attributes['user_id']);
        $this->assertInstanceOf(Carbon::class, $attributes['trip_date']);
        $this->assertInstanceOf(Carbon::class, $attributes['start_at']);
        $this->assertInstanceOf(Carbon::class, $attributes['end_at']);
        $this->assertTrue($attributes['end_at']->gt($attributes['start_at']));
        $this->assertNotSame('', $attributes['river_name']);
        $this->assertNotSame('', $attributes['point_name']);
        $this->assertNotSame('', $attributes['tackle_name']);
    }

    public function test_it_builds_photo_attributes(): void
    {
        $factory = new FishingTripSampleFactory();
        $attributes = $factory->makePhotoAttributes('trip-1', 3, 1, Carbon::parse('2026-04-10 12:00:00'));

        $this->assertTrue(Str::isUuid($attributes['id']));
        $this->assertSame('trip-1', $attributes['fishing_trip_id']);
        $this->assertSame(2, $attributes['sort_order']);
        $this->assertStringContainsString('Sample photo 2', $attributes['caption']);
        $this->assertInstanceOf(Carbon::class, $attributes['created_at']);
        $this->assertInstanceOf(Carbon::class, $attributes['updated_at']);
    }

    public function test_it_creates_a_placeholder_image_file(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagejpeg')) {
            $this->markTestSkipped('GD is not available.');
        }

        $factory = new FishingTripSampleFactory();
        $path = $factory->createPlaceholderImage(0, 0, '歴舟川');

        try {
            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path));
            $this->assertSame('jpg', pathinfo($path, PATHINFO_EXTENSION));
        } finally {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
