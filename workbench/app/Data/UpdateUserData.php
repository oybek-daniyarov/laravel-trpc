<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdateUserData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $avatar = null,
    ) {}
}
