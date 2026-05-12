<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use Artopia_Gallery\Artwork_Data;
use PHPUnit\Framework\TestCase;

final class ArtworkDataTest extends TestCase
{
    public function test_defaults_returns_full_canonical_shape(): void
    {
        self::assertSame(
            [
                'artist_id' => 0,
                'title' => '',
                'filename' => '',
                'medium' => '',
                'year' => 0,
                'dimensions' => '',
                'price' => '',
                'status' => 'available',
                'description' => '',
            ],
            Artwork_Data::defaults()
        );
    }

    public function test_normalize_applies_expected_field_rules(): void
    {
        $normalized = Artwork_Data::normalize([
            'artist_id' => '-42',
            'title' => '  <b>Yosemite in Winter</b>  ',
            'filename' => '  yosemite in winter.JPG  ',
            'medium' => '  <i>Acrylic on panel</i> ',
            'year' => '99999',
            'dimensions' => '  10 x 8 in  ',
            'price' => ' $1,800.00 ',
            'status' => 'archived',
            'description' => ' <p>Snowy ridge</p><script>alert(1)</script> ',
            'ignored' => 'value that should not survive',
        ]);

        self::assertSame(
            [
                'artist_id' => 42,
                'title' => 'Yosemite in Winter',
                'filename' => 'yosemite-in-winter.JPG',
                'medium' => 'Acrylic on panel',
                'year' => 9999,
                'dimensions' => '10 x 8 in',
                'price' => '1800.00',
                'status' => 'available',
                'description' => '<p>Snowy ridge</p>',
            ],
            $normalized
        );
    }

    public function test_build_import_key_is_stable_for_raw_and_normalized_values(): void
    {
        $raw = [
            'artist_id' => '12',
            'title' => '  Fathers  ',
            'filename' => ' fathers 2024.jpg ',
        ];

        $normalized = Artwork_Data::normalize($raw);

        self::assertSame(
            Artwork_Data::build_import_key($normalized),
            Artwork_Data::build_import_key($raw)
        );
    }

    public function test_normalize_status_preserves_known_values(): void
    {
        self::assertSame('print_available', Artwork_Data::normalize_status('print_available'));
    }
}
