@include('trpc::partials.file-header', ['description' => 'Combined API Client'])

import type { ApiClientConfig } from './core';
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupNamePascal = ucfirst(\Illuminate\Support\Str::camel($group));
@endphp
import { create{!! $groupNamePascal !!}Api, type {!! $groupNamePascal !!}Api } from './{!! $group !!}';
@endforeach

/** Combined API interface */
export interface Api {
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
@endphp
    {!! $groupName !!}: {!! $groupNamePascal !!}Api;
@endforeach
}

/**
 * Create a combined API client with all resource groups.
 *
 * @example
 * ```typescript
 * // Required: Must provide baseUrl configuration
 * const api = createApi({ baseUrl: process.env.NEXT_PUBLIC_API_URL! });
 *
 * // Use the API
 * const users = await api.users.index();
 * const user = await api.users.show({ user: 1 });
 * const post = await api.posts.show({ post: 1 });
 * ```
 *
 * @example
 * ```typescript
 * // With additional configuration
 * const api = createApi({
 *     baseUrl: process.env.NEXT_PUBLIC_API_URL!,
 *     headers: { 'X-App-Version': '1.0.0' },
 *     onError: (error) => console.error('API Error:', error),
 * });
 * ```
 */
export function createApi(config: ApiClientConfig): Api {
    return {
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
@endphp
        {!! $groupName !!}: create{!! $groupNamePascal !!}Api(config),
@endforeach
    };
}
