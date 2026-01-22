@include('trpc::partials.file-header', ['description' => ucfirst($group) . ' Barrel Exports'])

@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
@endphp
// Route definitions and types
export {
    {!! $groupName !!}Routes,
    type {!! $groupNamePascal !!}RouteName,
    type {!! $groupNamePascal !!}RouteTypeMap,
} from './routes';
@foreach($routes as $route)
@php
    $interface = $getInterfaceName($route['name']);
@endphp
export type { {!! $interface !!} } from './routes';
@endforeach

// API factory
export { create{!! $groupNamePascal !!}Api, type {!! $groupNamePascal !!}Api } from './api';
@if($hasQueries)

// React Query queries
export { {!! $groupName !!}Keys, create{!! $groupNamePascal !!}Queries } from './queries';
@endif
@if($hasMutations)

// React Query mutations
export { {!! $groupName !!}MutationKeys, create{!! $groupNamePascal !!}Mutations } from './mutations';
@endif
