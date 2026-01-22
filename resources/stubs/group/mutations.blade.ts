@include('trpc::partials.file-header', ['description' => ucfirst($group) . ' React Query Mutations'])

@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $mutationMethods = ['post', 'put', 'patch', 'delete'];
    $mutationRoutes = array_filter($routes, fn($r) => in_array($r['method'], $mutationMethods));
@endphp
import type { UseMutationOptions } from '@tanstack/react-query';
import type { RequestOptions } from '../core';
import type { {!! $groupNamePascal !!}Api } from './api';
import type { {!! $groupNamePascal !!}RouteTypeMap } from './routes';

/** Mutation keys for {!! $group !!} */
export const {!! $groupName !!}MutationKeys = {
    all: ['{!! $group !!}', 'mutation'] as const,
@foreach($mutationRoutes as $route)
@php
    $parts = explode('.', $route['name']);
    $routeGroup = $parts[0] ?? '';

    // Only remove first part if route name starts with the group name
    if ($routeGroup === $group && count($parts) > 1) {
        array_shift($parts);
    }

    $action = \Illuminate\Support\Str::camel(implode('_', $parts));
    if (empty($action)) {
        $action = 'mutate';
    }
@endphp
    {!! $action !!}: () => ['{!! $route['name'] !!}'] as const,
@endforeach
};
@foreach($mutationRoutes as $route)
@php
    $parts = explode('.', $route['name']);
    $routeGroup = $parts[0] ?? '';

    if ($routeGroup === $group && count($parts) > 1) {
        array_shift($parts);
    }

    $action = \Illuminate\Support\Str::camel(implode('_', $parts));
    if (empty($action)) {
        $action = 'mutate';
    }
    $actionPascal = ucfirst($action);

    $hasPathParams = !empty($route['pathParams']);
    $isGetOrDelete = in_array($route['method'], ['get', 'delete']);
    $hasBody = $route['hasRequest'] && !$isGetOrDelete;

    // Build variable type parts
    $varParts = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $varParts[] = "readonly $param: string | number";
        }
    }
    if ($hasBody) {
        $varParts[] = "readonly body: {$groupNamePascal}RouteTypeMap['{$route['name']}']['request']";
    }

    $needsVariables = !empty($varParts);
    $variablesType = $needsVariables ? '{ ' . implode('; ', $varParts) . ' }' : 'void';
@endphp

/** Variables for {!! $action !!} mutation */
export type {!! $actionPascal !!}Variables = {!! $variablesType !!};
@endforeach

/**
 * Create mutation options for {!! $group !!} bound to a specific API instance.
 *
 * @example
 * ```typescript
 * const api = create{!! $groupNamePascal !!}Api({ baseUrl: process.env.NEXT_PUBLIC_API_URL! });
 * const mutations = create{!! $groupNamePascal !!}Mutations(api);
 *
 * // Use in React Query
 * const mutation = useMutation(mutations.store());
@if(count($mutationRoutes) > 0)
@php
    $firstRoute = array_values($mutationRoutes)[0];
    $firstParts = explode('.', $firstRoute['name']);
    $firstRouteGroup = $firstParts[0] ?? '';
    if ($firstRouteGroup === $group && count($firstParts) > 1) {
        array_shift($firstParts);
    }
    $firstAction = \Illuminate\Support\Str::camel(implode('_', $firstParts));
    if (empty($firstAction)) {
        $firstAction = 'mutate';
    }
    $hasFirstBody = $firstRoute['hasRequest'] && !in_array($firstRoute['method'], ['get', 'delete']);
@endphp
@if($hasFirstBody)
 * mutation.mutate({ body: { ... } });
@else
 * mutation.mutate({ {!! !empty($firstRoute['pathParams']) ? $firstRoute['pathParams'][0] . ': 1' : '' !!} });
@endif
@endif
 * ```
 */
export function create{!! $groupNamePascal !!}Mutations(api: {!! $groupNamePascal !!}Api) {
    return {
        keys: {!! $groupName !!}MutationKeys,
@foreach($mutationRoutes as $route)
@php
    $parts = explode('.', $route['name']);
    $routeGroup = $parts[0] ?? '';

    if ($routeGroup === $group && count($parts) > 1) {
        array_shift($parts);
    }

    $action = \Illuminate\Support\Str::camel(implode('_', $parts));
    if (empty($action)) {
        $action = 'mutate';
    }
    $actionPascal = ucfirst($action);

    $hasPathParams = !empty($route['pathParams']);
    $isGetOrDelete = in_array($route['method'], ['get', 'delete']);
    $hasBody = $route['hasRequest'] && !$isGetOrDelete;

    // Build API call params
    $apiParams = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $apiParams[] = "$param: variables.$param";
        }
    }
    if ($hasBody) {
        $apiParams[] = "body: variables.body";
    }

    $needsVariables = $hasPathParams || $hasBody;
    $apiParamsStr = !empty($apiParams) ? '{ ' . implode(', ', $apiParams) . ' }' : '';
@endphp

        /**
         * {!! ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::snake($action))) !!} - {!! strtoupper($route['method']) !!} /{!! $route['path'] !!}
         */
        {!! $action !!}: (options?: RequestOptions): UseMutationOptions<
            {!! $groupNamePascal !!}RouteTypeMap['{!! $route['name'] !!}']['response'],
            {!! $groupNamePascal !!}RouteTypeMap['{!! $route['name'] !!}']['error'],
            {!! $actionPascal !!}Variables
        > => ({
            mutationKey: {!! $groupName !!}MutationKeys.{!! $action !!}(),
@if($needsVariables)
            mutationFn: (variables) => api.{!! $action !!}({!! $apiParamsStr !!}, options),
@else
            mutationFn: () => api.{!! $action !!}(options),
@endif
        }),
@endforeach
    };
}
