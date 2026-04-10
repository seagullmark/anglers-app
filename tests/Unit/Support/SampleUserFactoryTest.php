<?php

namespace Tests\Unit\Support;

use App\Support\SampleUserFactory;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SampleUserFactoryTest extends TestCase
{
    public function test_it_builds_sample_user_attributes(): void
    {
        $factory = new SampleUserFactory();
        $attributes = $factory->makeUserAttributes(
            sequence: 3,
            password: 'secret-password',
            now: Carbon::parse('2026-04-10 17:00:00'),
            emailPrefix: 'angler',
            emailDomain: 'example.com',
            namePrefix: 'Angler User',
        );

        $this->assertSame('Angler User 03', $attributes['name']);
        $this->assertSame('angler-20260410170000-03@example.com', $attributes['email']);
        $this->assertSame('secret-password', $attributes['password']);
        $this->assertArrayNotHasKey('id', $attributes);
        $this->assertArrayNotHasKey('remember_token', $attributes);
        $this->assertArrayNotHasKey('email_verified_at', $attributes);
    }
}
