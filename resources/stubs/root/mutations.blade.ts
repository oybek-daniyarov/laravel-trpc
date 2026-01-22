@include('trpc::partials.file-header', ['description' => 'Combined React Query Mutations'])

import type { Api } from './api';
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $mutationMethods = ['post', 'put', 'patch', 'delete'];
    $hasMutationRoutes = count(array_filter($groupedRoutes[$group], fn($r) => in_array($r['method'], $mutationMethods))) > 0;
@endphp
@if($hasMutationRoutes)
import { create{!! $groupNamePascal !!}Mutations } from './{!! $group !!}';
@endif
@endforeach

/**
 * Create mutation options bound to a configured API instance.
 *
 * @example
 * ```typescript
 * const api = createApi({ baseUrl: process.env.NEXT_PUBLIC_API_URL! });
 * const mutations = createMutations(api);
 *
 * // Use in React Query
 * const login = useMutation(mutations.auth.login());
 * const createUser = useMutation(mutations.users.store());
 * ```
 */
export function createMutations(api: Api) {
    return {
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $mutationMethods = ['post', 'put', 'patch', 'delete'];
    $hasMutationRoutes = count(array_filter($groupedRoutes[$group], fn($r) => in_array($r['method'], $mutationMethods))) > 0;
@endphp
@if($hasMutationRoutes)
        {!! $groupName !!}: create{!! $groupNamePascal !!}Mutations(api.{!! $groupName !!}),
@endif
@endforeach
    };
}
