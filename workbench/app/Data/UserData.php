<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Workbench\App\Enums\UserRole;

#[TypeScript]
final class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public UserRole $role = UserRole::User,
        public ?string $avatar = null,
        public ?string $created_at = null,
    ) {}
}
