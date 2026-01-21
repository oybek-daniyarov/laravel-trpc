<?php

declare(strict_types=1);

namespace Workbench\App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserRole: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';
    case Guest = 'guest';
}
