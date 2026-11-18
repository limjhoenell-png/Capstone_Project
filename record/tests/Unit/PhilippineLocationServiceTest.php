<?php

namespace Tests\Unit;

use App\Services\PhilippineLocationService;
use Tests\TestCase;

class PhilippineLocationServiceTest extends TestCase
{
    public function test_default_davao_address_codes_resolve_from_the_bundled_dataset(): void
    {
        $address = (new PhilippineLocationService)->resolveAddressCodes('11', '1124', '112403', '112403003');

        $this->assertSame([
            'region' => 'Region XI (Davao Region)',
            'province' => 'Davao del Sur',
            'city' => 'City of Digos',
            'barangay' => 'San Jose',
        ], $address);
    }

    public function test_address_codes_must_follow_the_philippine_geographic_hierarchy(): void
    {
        $this->assertNull(
            (new PhilippineLocationService)->resolveAddressCodes('11', '1124', '112403', '112402001'),
        );
    }
}
