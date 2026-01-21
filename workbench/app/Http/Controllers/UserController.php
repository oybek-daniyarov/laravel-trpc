<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use Workbench\App\Data\CreateUserData;
use Workbench\App\Data\UpdateUserData;
use Workbench\App\Data\UserData;
use Workbench\App\Data\UserQueryData;

final class UserController extends Controller
{
    #[TypedRoute(query: UserQueryData::class, response: UserData::class, isPaginated: true)]
    public function index(UserQueryData $query): JsonResponse
    {
        // Simulated paginated response
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 5,
                'per_page' => 10,
                'total' => 50,
            ],
        ]);
    }

    #[TypedRoute(response: UserData::class)]
    public function show(int $user): JsonResponse
    {
        return response()->json([
            'id' => $user,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[TypedRoute(request: CreateUserData::class, response: UserData::class)]
    public function store(CreateUserData $data): JsonResponse
    {
        return response()->json([
            'id' => 1,
            'name' => $data->name,
            'email' => $data->email,
        ], 201);
    }

    #[TypedRoute(request: UpdateUserData::class, response: UserData::class)]
    public function update(int $user, UpdateUserData $data): JsonResponse
    {
        return response()->json([
            'id' => $user,
            'name' => $data->name ?? 'John Doe',
            'email' => $data->email ?? 'john@example.com',
        ]);
    }

    public function destroy(int $user): JsonResponse
    {
        return response()->json(null, 204);
    }
}
