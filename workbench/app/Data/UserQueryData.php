<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserQueryData extends Data
{
    public function __construct(
        public ?int $page = null,
        public ?int $per_page = null,
        public ?string $search = null,
        public ?string $sort_by = null,
        public ?string $sort_dir = null,
        public ?string $role = null,
        public ?bool $active = null,
    ) {}
}
