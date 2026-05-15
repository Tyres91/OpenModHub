<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationCsvTest extends TestCase
{
    public function test_csv_file_exists(): void
    {
        $this->assertFileExists(resource_path('lang-source/translations.csv'));
    }

    public function test_csv_has_valid_structure(): void
    {
        $handle = fopen(resource_path('lang-source/translations.csv'), 'r');
        $header = fgetcsv($handle, 1000, ',', '"', '\\');

        $this->assertEquals(['key', 'en', 'de'], $header);

        fclose($handle);
    }

    public function test_csv_all_rows_have_both_languages(): void
    {
        $handle = fopen(resource_path('lang-source/translations.csv'), 'r');
        fgetcsv($handle);

        $rowNumber = 1;
        while (($row = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            $rowNumber++;

            if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            $this->assertGreaterThanOrEqual(
                3,
                count($row),
                "Row {$rowNumber} is missing language columns: ".implode(',', $row)
            );

            $this->assertNotEmpty(
                trim($row[1] ?? ''),
                "Row {$rowNumber} key '{$row[0]}' has empty English translation"
            );

            $this->assertNotEmpty(
                trim($row[2] ?? ''),
                "Row {$rowNumber} key '{$row[0]}' has empty German translation"
            );
        }

        fclose($handle);
    }

    public function test_csv_no_duplicate_keys(): void
    {
        $handle = fopen(resource_path('lang-source/translations.csv'), 'r');
        fgetcsv($handle);

        $keys = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            $rowNumber++;

            if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            $key = $row[0];
            if (isset($keys[$key])) {
                $this->fail("Duplicate key '{$key}' found at row {$rowNumber} (first seen at row {$keys[$key]})");
            }

            $keys[$key] = $rowNumber;
        }

        fclose($handle);

        $this->assertGreaterThan(0, count($keys), 'CSV should contain translation keys');
    }
}
