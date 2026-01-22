@include('trpc::partials.file-header', ['description' => 'Combined React Query Hooks'])

import type { Api } from './api';
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $hasGetRoutes = count(array_filter($groupedRoutes[$group], fn($r) => $r['method'] === 'get')) > 0;
@endphp
@if($hasGetRoutes)
import { create{!! $groupNamePascal !!}Queries } from './{!! $group !!}';
@endif
@endforeach

/**
 * Create query options bound to a configured API instance.
 *
 * @example
 * ```typescript
 * const api = createApi({ baseUrl: process.env.NEXT_PUBLIC_API_URL! });
 * const queries = createQueries(api);
 *
 * // Use in React Query
 * const { data } = useQuery(queries.users.show({ user: 1 }));
 * const { data: posts } = useQuery(queries.posts.index());
 * ```
 */
export function createQueries(api: Api) {
    return {
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $hasGetRoutes = count(array_filter($groupedRoutes[$group], fn($r) => $r['method'] === 'get')) > 0;
@endphp
@if($hasGetRoutes)
        {!! $groupName !!}: create{!! $groupNamePascal !!}Queries(api.{!! $groupName !!}),
@endif
@endforeach
    };
}
