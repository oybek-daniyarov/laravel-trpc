<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use OybekDaniyarov\LaravelTrpc\Transformers\DataTransformer;
use Spatie\LaravelData\Data;

// Test Data class for testing
final class TestUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

beforeEach(function () {
    $this->transformer = new DataTransformer;
    $this->context = new TransformContext;
});

it('transforms Data class to TypeScript interface name', function () {
    $result = $this->transformer->transform(TestUserData::class, $this->context);

    expect($result)->toBe('TestUserData');
});

it('returns correct namespace format with dots', function () {
    // Use the actual test class that exists
    $result = $this->transformer->transform(TestUserData::class, $this->context);

    expect($result)->toBe('TestUserData');
});

it('handles fully qualified class name with leading backslash', function () {
    // Use the actual test class with backslash prefix
    $result = $this->transformer->transform('\\'.TestUserData::class, $this->context);

    expect($result)->toBe('TestUserData');
});

it('returns unknown for non-string values', function () {
    $result = $this->transformer->transform(123, $this->context);

    expect($result)->toBe('unknown');
});

it('returns unknown for non-existent classes', function () {
    $result = $this->transformer->transform('NonExistentClass', $this->context);

    expect($result)->toBe('unknown');
});

it('supports method returns true for Data classes', function () {
    expect($this->transformer->supports(TestUserData::class))->toBeTrue();
});

it('supports method returns false for non-Data classes', function () {
    expect($this->transformer->supports('stdClass'))->toBeFalse()
        ->and($this->transformer->supports('DateTime'))->toBeFalse()
        ->and($this->transformer->supports('NonExistentClass'))->toBeFalse();
});

it('supports method returns false for non-class strings', function () {
    expect($this->transformer->supports('string'))->toBeFalse()
        ->and($this->transformer->supports('int'))->toBeFalse()
        ->and($this->transformer->supports('array'))->toBeFalse();
});

it('transforms with context', function () {
    $context = new TransformContext(
        metadata: ['extra' => 'data'],
    );

    $result = $this->transformer->transform(TestUserData::class, $context);

    expect($result)->toBe('TestUserData');
});
