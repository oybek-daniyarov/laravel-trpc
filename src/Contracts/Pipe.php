<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Contracts;

use Closure;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;

/**
 * Contract for pipeline pipes.
 *
 * Pipes are individual processors in the pipeline that transform
 * or enrich the payload as it flows through the generation process.
 */
interface Pipe
{
    /**
     * Handle the pipeline payload.
     *
     * @param  Closure(PipelinePayload): PipelinePayload  $next
     */
    public function handle(PipelinePayload $payload, Closure $next): PipelinePayload;
}
