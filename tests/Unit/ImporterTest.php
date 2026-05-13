<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use Artopia_Gallery\Importer;
use PHPUnit\Framework\TestCase;

final class TestableImporter extends Importer
{
    public function callNormalizeImportRow(array $row, int $artistId): array
    {
        return $this->normalize_import_row($row, $artistId);
    }

    public function callBuildRowImportKey(array $data): string
    {
        return $this->build_row_import_key($data);
    }

    public function callNormalizeColumnName(string $name): string
    {
        return $this->normalize_column_name($name);
    }

    public function callFindUnknownColumns(array $columns): array
    {
        return $this->find_unknown_columns($columns);
    }

    public function callRowIsEmpty(array $row): bool
    {
        return $this->row_is_empty($row);
    }
}

final class ImporterTest extends TestCase
{
    private TestableImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new TestableImporter();
    }

    public function testNormalizeImportRowAppliesArtworkDataRules(): void
    {
        $row = [
            'title' => '  <b>Yosemite in Winter</b>  ',
            'filename' => ' yosemite in winter.JPG ',
            'medium' => '  <i>Acrylic on panel</i> ',
            'year' => '99999',
            'dimensions' => ' 10 x 8 in ',
            'price' => ' $1,800.00 ',
            'status' => 'archived',
            'description' => ' <p>Snowy ridge</p><script>alert(1)</script> ',
        ];

        $normalized = $this->importer->callNormalizeImportRow($row, 12);

        self::assertSame(
            [
                'artist_id' => 12,
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

    public function testBuildRowImportKeyIsStableForEquivalentPayloads(): void
    {
        $first = [
            'artist_id' => 12,
            'title' => 'Fathers',
            'filename' => 'fathers-2024.jpg',
        ];

        $second = [
            'artist_id' => '12',
            'title' => '  Fathers  ',
            'filename' => ' fathers 2024.jpg ',
        ];

        self::assertSame(
            $this->importer->callBuildRowImportKey($first),
            $this->importer->callBuildRowImportKey($second)
        );
    }

    public function testBuildRowImportKeyChangesWhenTitleChanges(): void
    {
        $first = [
            'artist_id' => 12,
            'title' => 'Fathers',
            'filename' => 'fathers-2024.jpg',
        ];

        $second = [
            'artist_id' => 12,
            'title' => 'Fathers Variant',
            'filename' => 'fathers-2024.jpg',
        ];

        self::assertNotSame(
            $this->importer->callBuildRowImportKey($first),
            $this->importer->callBuildRowImportKey($second)
        );
    }

    public function testNormalizeColumnNameAppliesAliasesAndFormatting(): void
    {
        self::assertSame('filename', $this->importer->callNormalizeColumnName('image'));
        self::assertSame('title', $this->importer->callNormalizeColumnName('name'));
        self::assertSame('dimensions', $this->importer->callNormalizeColumnName('size'));
        self::assertSame('description', $this->importer->callNormalizeColumnName('artist statement'));
        self::assertSame('artwork_title', $this->importer->callNormalizeColumnName('Artwork-Title'));
    }

    public function testFindUnknownColumnsReturnsOnlyUnknownValues(): void
    {
        $unknown = $this->importer->callFindUnknownColumns([
            'filename',
            'title',
            'medium',
            'year',
            'dimensions',
            'price',
            'status',
            'description',
            'collection_code',
            'inventory_note',
        ]);

        self::assertSame(
            ['collection_code', 'inventory_note'],
            $unknown
        );
    }

    public function testRowIsEmptyHandlesBlankAndNonBlankRows(): void
    {
        self::assertTrue($this->importer->callRowIsEmpty(['', '', '']));
        self::assertTrue($this->importer->callRowIsEmpty(['  ', "\t", "\n"]));
        self::assertFalse($this->importer->callRowIsEmpty(['', 'value', '']));
    }
}
