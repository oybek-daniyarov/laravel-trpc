<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Workbench\App\Enums\PostStatus;

#[TypeScript]
final class PostData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public int $user_id,
        public PostStatus $status = PostStatus::Draft,
        public ?UserData $author = null,
        /** @var array<CommentData>|null */
        #[DataCollectionOf(CommentData::class)]
        public ?array $comments = null,
        public ?string $published_at = null,
        public ?string $created_at = null,
    ) {}
}
