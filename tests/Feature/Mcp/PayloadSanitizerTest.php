<?php

use App\Mcp\Support\PayloadSanitizer;

it('redacts denylisted keys at any depth', function () {
    $clean = PayloadSanitizer::sanitize([
        'method' => 'GET',
        'headers' => [
            'Authorization' => 'Bearer secret-token',
            'Cookie' => 'session=abc',
            'Accept' => 'application/json',
        ],
        'password' => 'hunter2',
    ]);

    expect($clean['method'])->toBe('GET')
        ->and($clean['headers']['Authorization'])->toBe('[REDACTED]')
        ->and($clean['headers']['Cookie'])->toBe('[REDACTED]')
        ->and($clean['headers']['Accept'])->toBe('application/json')
        ->and($clean['password'])->toBe('[REDACTED]');
});

it('truncates long string values', function () {
    $long = str_repeat('x', 5000);

    $clean = PayloadSanitizer::sanitize(['sql' => $long]);

    expect(strlen($clean['sql']))->toBeLessThan(2100)
        ->and($clean['sql'])->toEndWith('...');
});
