<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CreatePostData extends Data
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}
}
