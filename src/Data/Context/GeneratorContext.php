<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Context;

use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Context object for generators.
 *
 * Provides generators with output configuration and generation options.
 */
final readonly class GeneratorContext
{
    public function __construct(
        public string $outputPath,
        public TrpcConfig $config,
        public bool $postman = false,
        public bool $postmanEnv = false,
        public bool $skipTypeScriptTransform = false,
    ) {}

    /**
     * Create a context for TypeScript generation.
     */
    public static function forTypeScript(TrpcConfig $config): self
    {
        return new self(
            outputPath: $config->getOutputPath(),
            config: $config,
        );
    }

    /**
     * Create a context for Postman generation.
     */
    public static function forPostman(TrpcConfig $config, bool $withEnvironment = false): self
    {
        return new self(
            outputPath: $config->getPostmanOutputPath(),
            config: $config,
            postman: true,
            postmanEnv: $withEnvironment,
        );
    }
}
