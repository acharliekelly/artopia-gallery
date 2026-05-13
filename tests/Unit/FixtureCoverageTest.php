<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FixtureCoverageTest extends TestCase
{
    private function fixturePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/fixtures/' . $name;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function readCsv(string $name): array
    {
        $path = $this->fixturePath($name);
        $handle = fopen($path, 'r');

        self::assertNotFalse($handle, sprintf('Could not open fixture: %s', $name));

        $headers = fgetcsv($handle);
        self::assertIsArray($headers, sprintf('Missing header row in fixture: %s', $name));

        $headers = array_map(
            static fn($value): string => is_string($value) ? $value : '',
            $headers
        );

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = isset($row[$index]) ? (string) $row[$index] : '';
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @return array<int, string>
     */
    private function columnValues(array $rows, string $column): array
    {
        return array_map(
            static fn(array $row): string => $row[$column] ?? '',
            $rows
        );
    }

    public function testImportMinFixtureHasMinimalValidShape(): void
    {
        $csv = $this->readCsv('import_min.csv');

        self::assertSame(['filename', 'title'], $csv['headers']);
        self::assertCount(2, $csv['rows']);

        $filenames = [];
        foreach ($csv['rows'] as $row) {
            self::assertNotSame('', trim($row['filename']));
            self::assertNotSame('', trim($row['title']));
            $filenames[] = trim($row['filename']);
        }

        self::assertCount(count(array_unique($filenames)), $filenames);
    }

    public function testImportDefaultFixtureHasExpectedHappyPathShape(): void
    {
        $csv = $this->readCsv('import_default.csv');

        self::assertSame(
            ['filename', 'title', 'medium', 'year', 'dimensions', 'price', 'status', 'description'],
            $csv['headers']
        );

        self::assertCount(8, $csv['rows']);

        $allowedStatuses = ['available', 'sold', 'inquiry', 'print_available'];
        $filenames = [];

        foreach ($csv['rows'] as $row) {
            self::assertNotSame('', trim($row['filename']));
            self::assertNotSame('', trim($row['title']));
            self::assertContains($row['status'], $allowedStatuses);
            $filenames[] = trim($row['filename']);
        }

        self::assertCount(count(array_unique($filenames)), $filenames);
    }

    public function testImportBadFixtureContainsExpectedFailureScenarios(): void
    {
        $csv = $this->readCsv('import_bad.csv');

        self::assertCount(4, $csv['rows']);

        $hasEmptyFilename = false;
        $hasEmptyTitle = false;
        $hasInvalidStatus = false;
        $hasNonDigitYear = false;

        $allowedStatuses = ['available', 'sold', 'inquiry', 'print_available'];

        foreach ($csv['rows'] as $row) {
            if (trim($row['filename']) === '') {
                $hasEmptyFilename = true;
            }

            if (trim($row['title']) === '') {
                $hasEmptyTitle = true;
            }

            if (($row['status'] ?? '') !== '' && !in_array($row['status'], $allowedStatuses, true)) {
                $hasInvalidStatus = true;
            }

            $year = trim($row['year'] ?? '');
            /** @disregard unrecognized function ctype_digit */
            if ($year !== '' && !ctype_digit($year)) {
                $hasNonDigitYear = true;
            }
        }

        self::assertTrue($hasEmptyFilename);
        self::assertTrue($hasEmptyTitle);
        self::assertTrue($hasInvalidStatus);
        self::assertTrue($hasNonDigitYear);
    }

    public function testImportAbnormalFixtureContainsExpectedNormalizationScenarios(): void
    {
        $csv = $this->readCsv('import_abnormal.csv');

        self::assertSame(
            [' image ', ' name ', ' medium ', ' year ', ' size ', ' price ', ' status ', ' artist_statement'],
            $csv['headers']
        );

        self::assertCount(4, $csv['rows']);

        $hasCurrencyFormatting = false;
        $hasYearAbove9999 = false;
        $hasUnexpectedStatus = false;

        foreach ($csv['rows'] as $row) {
            $price = $row[' price '] ?? '';
            $year = trim($row[' year '] ?? '');
            $status = trim($row[' status '] ?? '');

            if (str_contains($price, '$') || str_contains($price, ',')) {
                $hasCurrencyFormatting = true;
            }

            /** @disregard unrecognized function ctype_digit */
            if ($year !== '' && ctype_digit($year) && (int) $year > 9999) {
                $hasYearAbove9999 = true;
            }

            if ($status !== '' && !in_array($status, ['available', 'sold', 'inquiry', 'print_available'], true)) {
                $hasUnexpectedStatus = true;
            }
        }

        self::assertTrue($hasCurrencyFormatting);
        self::assertTrue($hasYearAbove9999);
        self::assertTrue($hasUnexpectedStatus);
    }

    public function testImportDuplicatesFixtureIsSubsetOfDefaultFixture(): void
    {
        $default = $this->readCsv('import_default.csv');
        $duplicates = $this->readCsv('import_duplicates.csv');

        self::assertSame($default['headers'], $duplicates['headers']);
        self::assertCount(4, $duplicates['rows']);

        $defaultFilenames = $this->columnValues($default['rows'], 'filename');
        $duplicateFilenames = $this->columnValues($duplicates['rows'], 'filename');

        foreach ($duplicateFilenames as $filename) {
            self::assertContains($filename, $defaultFilenames);
        }
    }

    public function testImportSameFilenameDifferentTitleFixturePreservesItsScenario(): void
    {
        $duplicates = $this->readCsv('import_duplicates.csv');
        $sameFilenameDifferentTitle = $this->readCsv('import_same_filename_diff_title.csv');

        self::assertSame($duplicates['headers'], $sameFilenameDifferentTitle['headers']);
        self::assertCount(4, $sameFilenameDifferentTitle['rows']);

        $duplicateByFilename = [];
        foreach ($duplicates['rows'] as $row) {
            $duplicateByFilename[$row['filename']] = $row;
        }

        $differentTitleCount = 0;

        foreach ($sameFilenameDifferentTitle['rows'] as $row) {
            $filename = $row['filename'];

            self::assertArrayHasKey($filename, $duplicateByFilename);

            if (($duplicateByFilename[$filename]['title'] ?? '') !== ($row['title'] ?? '')) {
                $differentTitleCount++;
            }
        }

        self::assertGreaterThan(
            0,
            $differentTitleCount,
            'Expected at least one matching filename to have a different title.'
        );
    }
}
