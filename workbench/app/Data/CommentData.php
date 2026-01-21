<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CommentData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public int $post_id,
        public int $user_id,
        public ?string $created_at = null,
    ) {}
}
