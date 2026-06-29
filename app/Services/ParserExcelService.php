<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ParserExcelService
{
    /**
     * Estrae il contenuto di un file Excel via microservizio Python.
     *
     * @return array{success: bool, nome_file: string, fogli: array<int, array<string, mixed>>, warnings: array<int, string>}
     *
     * @throws ConnectionException se il microservizio non è raggiungibile
     */
    public function estrai(string $pathAssoluto, bool $includiRecord = false): array
    {
        $url = rtrim((string) config('services.parser.url'), '/').'/parse-excel';

        $response = Http::timeout(60)->post($url, [
            'path' => $pathAssoluto,
            'includi_record' => $includiRecord,
        ]);

        $response->throw();

        return $response->json();
    }
}
