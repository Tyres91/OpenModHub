<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTranslations extends Command
{
    protected $signature = 'translations:generate {--force : Overwrite existing files}';

    protected $description = 'Generate PHP translation files from the CSV source';

    public function handle(): int
    {
        $csvPath = resource_path('lang-source/translations.csv');

        if (! File::exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");

            return Command::FAILURE;
        }

        $translations = $this->parseCsv($csvPath);

        $langPath = base_path('lang');

        foreach (['en', 'de'] as $locale) {
            $filePath = "{$langPath}/{$locale}/messages.php";
            $data = $translations[$locale] ?? [];

            if (File::exists($filePath) && ! $this->option('force')) {
                if (! $this->confirm("{$filePath} already exists. Overwrite?")) {
                    continue;
                }
            }

            $content = $this->arrayToPhp($data);
            File::put($filePath, $content);

            $this->info("Generated: {$filePath}");
        }

        $this->info('Translation files generated successfully.');

        return Command::SUCCESS;
    }

    protected function parseCsv(string $csvPath): array
    {
        $translations = [];
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return $translations;
        }

        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $key = $row[0];
            $en = $row[1];
            $de = $row[2];

            $this->setNestedValue($translations, $key, 'en', $en);
            $this->setNestedValue($translations, $key, 'de', $de);
        }

        fclose($handle);

        return $translations;
    }

    protected function setNestedValue(array &$array, string $dotKey, string $locale, string $value): void
    {
        $keys = explode('.', $dotKey);

        if (! isset($array[$locale])) {
            $array[$locale] = [];
        }

        $current = &$array[$locale];

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (! isset($current[$key])) {
                    $current[$key] = [];
                } elseif (! is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }

    protected function arrayToPhp(array $array, int $indent = 0): string
    {
        $php = "<?php\n\nreturn [\n";
        $php .= $this->arrayToString($array, $indent + 1);
        $php .= "];\n";

        return $php;
    }

    protected function arrayToString(array $array, int $indent = 1): string
    {
        $result = '';
        $pad = str_repeat('    ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result .= "{$pad}'{$key}' => [\n";
                $result .= $this->arrayToString($value, $indent + 1);
                $result .= "{$pad}],\n";
            } else {
                $escaped = $this->escapePhpString($value);
                $result .= "{$pad}'{$key}' => '{$escaped}',\n";
            }
        }

        return $result;
    }

    protected function escapePhpString(string $value): string
    {
        return addcslashes($value, "'\\");
    }
}
