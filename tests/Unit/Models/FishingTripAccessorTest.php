<?php

namespace Tests\Unit\Models;

use App\Models\FishingTrip;
use PHPUnit\Framework\TestCase;

class FishingTripAccessorTest extends TestCase
{
    public function test_it_formats_trip_dates_for_list_and_form_usage(): void
    {
        $trip = new FishingTrip();
        $trip->setRawAttributes([
            'trip_date' => '4/10/2026',
            'start_at' => '4/10/2026 05:30:00',
            'end_at' => '4/10/2026 09:15:00',
        ], true);

        $this->assertSame('2026-04-10', $trip->trip_date_label);
        $this->assertSame('05:30', $trip->start_time);
        $this->assertSame('09:15', $trip->end_time);
        $this->assertSame('2026-04-10T05:30', $trip->start_at_input);
        $this->assertSame('2026-04-10T09:15', $trip->end_at_input);
    }
}
