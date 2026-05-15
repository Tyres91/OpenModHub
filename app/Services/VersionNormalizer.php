<?php

namespace App\Services;

use Composer\Semver\VersionParser;
use UnexpectedValueException;

class VersionNormalizer
{
    public function normalize(string $version): ?string
    {
        $version = trim($version);

        if ($version === '') {
            return null;
        }

        try {
            return (new VersionParser)->normalize($version);
        } catch (UnexpectedValueException) {
            return null;
        }
    }
}
