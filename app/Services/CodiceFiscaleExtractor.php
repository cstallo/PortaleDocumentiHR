<?php

namespace App\Services;

class CodiceFiscaleExtractor
{
    // const CF_REGEX = '/\(([A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z])-[0-9]+\)/i';
    const CF_REGEX = '/\(([A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z])-[0-9]+\)/i';


    public function extract(string $filename): ?string
    {
        $basename = basename($filename);
        if (preg_match(self::CF_REGEX, $basename, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }

    public function isValid(string $filename): bool
    {
        return $this->extract($filename) !== null;
    }
}
