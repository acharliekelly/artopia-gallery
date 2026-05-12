<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use Artopia_Gallery\Helpers;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function test_artwork_statuses_exposes_expected_keys(): void
    {
        $statuses = Helpers::artwork_statuses();

        self::assertSame(
            ['available', 'sold', 'inquiry', 'print_available'],
            array_keys($statuses)
        );
    }

    public function test_valid_artwork_status_accepts_known_value(): void
    {
        self::assertTrue(Helpers::valid_artwork_status('sold'));
    }

    public function test_valid_artwork_status_rejects_unknown_value(): void
    {
        self::assertFalse(Helpers::valid_artwork_status('archived'));
    }

    public function test_normalize_artwork_status_defaults_unknown_to_available(): void
    {
        self::assertSame('available', Helpers::normalize_artwork_status('archived'));
    }
}
