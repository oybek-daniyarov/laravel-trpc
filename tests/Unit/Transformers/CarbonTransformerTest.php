<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon as IlluminateCarbon;
use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use OybekDaniyarov\LaravelTrpc\Transformers\CarbonTransformer;

beforeEach(function () {
    $this->transformer = new CarbonTransformer;
    $this->context = new TransformContext;
});

it('transforms Carbon to string', function () {
    $result = $this->transformer->transform(Carbon::class, $this->context);

    expect($result)->toBe('string');
});

it('transforms CarbonImmutable to string', function () {
    $result = $this->transformer->transform(CarbonImmutable::class, $this->context);

    expect($result)->toBe('string');
});

it('transforms Illuminate Carbon to string', function () {
    $result = $this->transformer->transform(IlluminateCarbon::class, $this->context);

    expect($result)->toBe('string');
});

it('transforms DateTimeInterface to string', function () {
    $result = $this->transformer->transform(DateTimeInterface::class, $this->context);

    expect($result)->toBe('string');
});

it('transforms DateTimeImmutable to string', function () {
    $result = $this->transformer->transform(DateTimeImmutable::class, $this->context);

    expect($result)->toBe('string');
});

it('supports method returns true for Carbon class', function () {
    expect($this->transformer->supports(Carbon::class))->toBeTrue();
});

it('supports method returns true for CarbonImmutable class', function () {
    expect($this->transformer->supports(CarbonImmutable::class))->toBeTrue();
});

it('supports method returns true for Illuminate Carbon class', function () {
    expect($this->transformer->supports(IlluminateCarbon::class))->toBeTrue();
});

it('supports method returns true for DateTimeInterface', function () {
    expect($this->transformer->supports(DateTimeInterface::class))->toBeTrue();
});

it('supports method returns true for DateTimeImmutable', function () {
    expect($this->transformer->supports(DateTimeImmutable::class))->toBeTrue();
});

it('supports method returns false for non-date types', function () {
    expect($this->transformer->supports('stdClass'))->toBeFalse()
        ->and($this->transformer->supports('string'))->toBeFalse()
        ->and($this->transformer->supports('int'))->toBeFalse();
});

it('supports method returns false for non-existent classes', function () {
    expect($this->transformer->supports('NonExistentClass'))->toBeFalse();
});

it('always returns string regardless of input value', function () {
    // The transform method always returns 'string' for date types
    $result1 = $this->transformer->transform(Carbon::class, $this->context);
    $result2 = $this->transformer->transform(CarbonImmutable::class, $this->context);
    $result3 = $this->transformer->transform(DateTimeInterface::class, $this->context);

    expect($result1)->toBe('string')
        ->and($result2)->toBe('string')
        ->and($result3)->toBe('string');
});

it('handles context with metadata', function () {
    $context = new TransformContext(
        metadata: ['format' => 'iso8601'],
    );

    $result = $this->transformer->transform(Carbon::class, $context);

    expect($result)->toBe('string');
});
