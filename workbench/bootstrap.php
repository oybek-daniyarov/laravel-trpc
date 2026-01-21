<?php

/**
 * Workbench Bootstrap File
 *
 * This file is used for local development testing.
 * Usage: ./vendor/bin/testbench serve
 */

declare(strict_types=1);

// Ensure auto_typescript_transform is disabled in development
// since spatie/typescript-transformer may not be available
putenv('TYPED_API_AUTO_TRANSFORM=false');
