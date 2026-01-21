<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\TransformerCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;
use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;

it('can add and retrieve transformers', function () {
    $collection = new TransformerCollection;
    $transformer = createMockTransformer();

    $collection->add('TestClass', $transformer);

    expect($collection->findForType('TestClass'))->toBe($transformer);
});

it('can check if transformer exists', function () {
    $collection = new TransformerCollection;
    $transformer = createMockTransformer();

    $collection->add('TestClass', $transformer);

    expect($collection->has('TestClass'))->toBeTrue()
        ->and($collection->has('NonExistent'))->toBeFalse();
});

it('can merge transformer collections', function () {
    $collection1 = new TransformerCollection;
    $collection2 = new TransformerCollection;

    $transformer1 = createMockTransformer();
    $transformer2 = createMockTransformer();

    $collection1->add('Class1', $transformer1);
    $collection2->add('Class2', $transformer2);

    $merged = $collection1->merge($collection2);

    expect($merged->findForType('Class1'))->toBe($transformer1)
        ->and($merged->findForType('Class2'))->toBe($transformer2);
});

it('returns all transformers', function () {
    $collection = new TransformerCollection;
    $transformer1 = createMockTransformer();
    $transformer2 = createMockTransformer();

    $collection->add('Class1', $transformer1);
    $collection->add('Class2', $transformer2);

    expect($collection->all())->toHaveCount(2);
});

// Helper function to create mock transformers
function createMockTransformer(): Transformer
{
    return new class implements Transformer
    {
        public function transform(mixed $value, TransformContext $context): string
        {
            return 'transformed';
        }

        public function supports(string $type): bool
        {
            return false;
        }
    };
}
