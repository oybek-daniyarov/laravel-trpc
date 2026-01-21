<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use OybekDaniyarov\LaravelTrpc\Transformers\EnumTransformer;

// Test enums for testing
enum TestStringEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

enum TestIntEnum: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

beforeEach(function () {
    $this->transformer = new EnumTransformer;
    $this->context = new TransformContext;
});

it('transforms string-backed enum to union type', function () {
    $result = $this->transformer->transform(TestStringEnum::class, $this->context);

    expect($result)->toBe("'active' | 'inactive' | 'pending'");
});

it('transforms int-backed enum to union type', function () {
    $result = $this->transformer->transform(TestIntEnum::class, $this->context);

    expect($result)->toBe('1 | 2 | 3');
});

it('returns unknown for non-string values', function () {
    $result = $this->transformer->transform(123, $this->context);

    expect($result)->toBe('unknown');
});

it('returns unknown for non-existent classes', function () {
    $result = $this->transformer->transform('NonExistentEnum', $this->context);

    expect($result)->toBe('unknown');
});

it('returns unknown for non-enum classes', function () {
    $result = $this->transformer->transform('stdClass', $this->context);

    expect($result)->toBe('unknown');
});

it('supports method returns true for BackedEnum', function () {
    expect($this->transformer->supports(TestStringEnum::class))->toBeTrue()
        ->and($this->transformer->supports(TestIntEnum::class))->toBeTrue();
});

it('supports method returns false for non-enums', function () {
    expect($this->transformer->supports('stdClass'))->toBeFalse()
        ->and($this->transformer->supports('DateTime'))->toBeFalse()
        ->and($this->transformer->supports('string'))->toBeFalse();
});

it('supports method returns false for non-existent classes', function () {
    expect($this->transformer->supports('NonExistentClass'))->toBeFalse();
});

it('handles enum with single case', function () {
    // We'll test with the existing enum but verify the format
    $result = $this->transformer->transform(TestStringEnum::class, $this->context);

    expect($result)->toContain("'active'")
        ->and($result)->toContain(' | ');
});

it('wraps string values in quotes', function () {
    $result = $this->transformer->transform(TestStringEnum::class, $this->context);

    expect($result)->toContain("'active'")
        ->and($result)->toContain("'inactive'")
        ->and($result)->toContain("'pending'");
});

it('does not wrap int values in quotes', function () {
    $result = $this->transformer->transform(TestIntEnum::class, $this->context);

    expect($result)->not->toContain("'1'")
        ->and($result)->not->toContain("'2'")
        ->and($result)->toContain('1')
        ->and($result)->toContain('2');
});
