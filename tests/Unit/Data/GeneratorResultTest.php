<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;

it('creates with files array', function () {
    $result = new GeneratorResult([
        'test.ts' => 'const test = true;',
        'types.ts' => 'export interface Test {}',
    ]);

    expect($result->files)->toHaveCount(2)
        ->and($result->files)->toHaveKey('test.ts')
        ->and($result->files)->toHaveKey('types.ts');
});

it('merge() combines two results', function () {
    $result1 = new GeneratorResult([
        'file1.ts' => 'content1',
    ]);

    $result2 = new GeneratorResult([
        'file2.ts' => 'content2',
    ]);

    $merged = $result1->merge($result2);

    expect($merged->files)->toHaveCount(2)
        ->and($merged->files)->toHaveKey('file1.ts')
        ->and($merged->files)->toHaveKey('file2.ts')
        ->and($merged->files['file1.ts'])->toBe('content1')
        ->and($merged->files['file2.ts'])->toBe('content2');
});

it('merge() overwrites duplicate files', function () {
    $result1 = new GeneratorResult([
        'file.ts' => 'original',
    ]);

    $result2 = new GeneratorResult([
        'file.ts' => 'overwritten',
    ]);

    $merged = $result1->merge($result2);

    expect($merged->files['file.ts'])->toBe('overwritten');
});

it('isEmpty() returns true when no files', function () {
    $result = new GeneratorResult([]);

    expect($result->isEmpty())->toBeTrue();
});

it('isEmpty() returns false when has files', function () {
    $result = new GeneratorResult([
        'test.ts' => 'content',
    ]);

    expect($result->isEmpty())->toBeFalse();
});

it('getTotalSize() calculates total bytes', function () {
    $result = new GeneratorResult([
        'file1.ts' => '12345', // 5 bytes
        'file2.ts' => '1234567890', // 10 bytes
    ]);

    expect($result->getTotalSize())->toBe(15);
});

it('getTotalSize() returns 0 for empty result', function () {
    $result = new GeneratorResult([]);

    expect($result->getTotalSize())->toBe(0);
});

it('is readonly', function () {
    $result = new GeneratorResult(['test.ts' => 'content']);

    // This should work because files is a public readonly property
    expect($result->files)->toBe(['test.ts' => 'content']);
});

it('merge returns new instance', function () {
    $result1 = new GeneratorResult(['file1.ts' => 'content1']);
    $result2 = new GeneratorResult(['file2.ts' => 'content2']);

    $merged = $result1->merge($result2);

    // Original results should be unchanged
    expect($result1->files)->toHaveCount(1)
        ->and($result2->files)->toHaveCount(1)
        ->and($merged)->not->toBe($result1)
        ->and($merged)->not->toBe($result2);
});

it('handles empty merge', function () {
    $result1 = new GeneratorResult(['file.ts' => 'content']);
    $result2 = new GeneratorResult([]);

    $merged = $result1->merge($result2);

    expect($merged->files)->toBe(['file.ts' => 'content']);
});

it('handles unicode content in size calculation', function () {
    $result = new GeneratorResult([
        'test.ts' => 'Hello 世界', // "世界" is 6 bytes in UTF-8
    ]);

    // getTotalSize uses strlen (bytes), not mb_strlen (characters)
    // "Hello " = 6 bytes, "世界" = 6 bytes = 12 bytes total
    expect($result->getTotalSize())->toBe(12);
});
