# Upgrade Guide

## Upgrading to 0.2.x from 0.1.x

_(Reserved for future breaking changes)_

## General Upgrade Steps

1. Update the package version in `composer.json`
2. Run `composer update oybek-daniyarov/laravel-trpc`
3. Regenerate your TypeScript client: `php artisan trpc:generate --force`
4. Review the generated output for any changes
5. Run your TypeScript build to catch any type errors

## Regenerating After Upgrade

After any upgrade, always regenerate your TypeScript files:

```bash
php artisan trpc:generate --force
```

Then run your TypeScript type checker to verify compatibility:

```bash
npm run typecheck  # or tsc --noEmit
```
