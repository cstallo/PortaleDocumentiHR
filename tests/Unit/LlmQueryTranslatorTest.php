<?php

use App\Services\LlmQueryTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('document request without period is normalized to latest document intent', function () {
    config(['services.anthropic.key' => 'test-key']);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'intento' => 'valore',
                        'dipendente' => 'stallo',
                        'campo' => null,
                        'anno' => null,
                        'mese' => null,
                    ]),
                ],
            ],
        ]),
    ]);

    $result = app(LlmQueryTranslator::class)->translate('mostrami il cedolino di stallo');

    expect($result)
        ->toMatchArray([
            'ok' => true,
            'intento' => 'documento',
            'dipendente' => 'stallo',
            'anno' => null,
            'mese' => null,
        ])
        ->not->toHaveKey('campo');
});

test('value request without period keeps latest value intent', function () {
    config(['services.anthropic.key' => 'test-key']);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'intento' => 'documento',
                        'dipendente' => 'stallo',
                        'anno' => null,
                        'mese' => null,
                    ]),
                ],
            ],
        ]),
    ]);

    $result = app(LlmQueryTranslator::class)->translate('qual è il netto di stallo?');

    expect($result)->toMatchArray([
        'ok' => true,
        'intento' => 'valore',
        'dipendente' => 'stallo',
        'campo' => 'netto',
        'anno' => null,
        'mese' => null,
    ]);
});

test('value request mentioning payslip is not forced to document intent', function () {
    config(['services.anthropic.key' => 'test-key']);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'intento' => 'documento',
                        'dipendente' => 'stallo',
                        'anno' => null,
                        'mese' => null,
                    ]),
                ],
            ],
        ]),
    ]);

    $result = app(LlmQueryTranslator::class)->translate('mostrami il netto del cedolino di stallo');

    expect($result)->toMatchArray([
        'ok' => true,
        'intento' => 'valore',
        'dipendente' => 'stallo',
        'campo' => 'netto',
        'anno' => null,
        'mese' => null,
    ]);
});
