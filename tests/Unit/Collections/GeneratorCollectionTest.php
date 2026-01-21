<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\GeneratorCollection;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Generator;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Create a test generator for testing
final class TestGenerator implements Generator
{
    public function generate(RouteCollection $routes, GeneratorContext $context): GeneratorResult
    {
        return new GeneratorResult(['test.txt' => 'test content']);
    }
}

beforeEach(function () {
    $this->collection = new GeneratorCollection;
});

it('adds generator to collection', function () {
    $generator = new TestGenerator;

    $this->collection->add('test', $generator);

    expect($this->collection->has('test'))->toBeTrue();
});

it('gets generator by name', function () {
    $generator = new TestGenerator;

    $this->collection->add('test', $generator);

    $retrieved = $this->collection->get('test');

    expect($retrieved)->toBe($generator);
});

it('throws exception for non-existent generator', function () {
    $this->collection->get('nonexistent');
})->throws(InvalidArgumentException::class, "Generator 'nonexistent' not found.");

it('checks if generator exists', function () {
    $generator = new TestGenerator;

    $this->collection->add('test', $generator);

    expect($this->collection->has('test'))->toBeTrue()
        ->and($this->collection->has('nonexistent'))->toBeFalse();
});

it('returns all generators', function () {
    $generator1 = new TestGenerator;
    $generator2 = new TestGenerator;

    $this->collection->add('gen1', $generator1);
    $this->collection->add('gen2', $generator2);

    $all = $this->collection->all();

    expect($all)->toBeArray()
        ->and($all)->toHaveCount(2)
        ->and($all['gen1'])->toBe($generator1)
        ->and($all['gen2'])->toBe($generator2);
});

it('returns all generator names', function () {
    $this->collection->add('typescript', new TestGenerator);
    $this->collection->add('postman', new TestGenerator);

    $names = $this->collection->names();

    expect($names)->toBe(['typescript', 'postman']);
});

it('returns empty array for empty collection', function () {
    expect($this->collection->all())->toBe([])
        ->and($this->collection->names())->toBe([]);
});

it('supports method chaining when adding', function () {
    $result = $this->collection
        ->add('gen1', new TestGenerator)
        ->add('gen2', new TestGenerator);

    expect($result)->toBe($this->collection)
        ->and($this->collection->has('gen1'))->toBeTrue()
        ->and($this->collection->has('gen2'))->toBeTrue();
});

it('overwrites generator with same name', function () {
    $generator1 = new TestGenerator;
    $generator2 = new TestGenerator;

    $this->collection->add('test', $generator1);
    $this->collection->add('test', $generator2);

    expect($this->collection->get('test'))->toBe($generator2);
});

it('works with real generator classes', function () {
    $config = new TrpcConfig([
        'output_path' => '/tmp/api',
    ]);

    $tsGenerator = app(OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator::class, [
        'config' => $config,
    ]);

    $postmanGenerator = app(OybekDaniyarov\LaravelTrpc\Generators\PostmanGenerator::class, [
        'config' => $config,
    ]);

    $this->collection->add('typescript', $tsGenerator);
    $this->collection->add('postman', $postmanGenerator);

    expect($this->collection->has('typescript'))->toBeTrue()
        ->and($this->collection->has('postman'))->toBeTrue()
        ->and($this->collection->get('typescript'))->toBe($tsGenerator)
        ->and($this->collection->get('postman'))->toBe($postmanGenerator);
});
