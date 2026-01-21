<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthTokenData extends Data
{
    public function __construct(
        public string $token,
        public string $type,
        public ?int $expires_in = null,
    ) {}
}
