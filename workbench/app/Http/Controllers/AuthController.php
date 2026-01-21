<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use Workbench\App\Data\AuthTokenData;
use Workbench\App\Data\LoginData;
use Workbench\App\Data\RegisterData;
use Workbench\App\Data\UserData;

final class AuthController extends Controller
{
    #[TypedRoute(request: LoginData::class, response: AuthTokenData::class)]
    public function login(LoginData $data): JsonResponse
    {
        return response()->json([
            'token' => 'fake-jwt-token-123',
            'type' => 'bearer',
            'expires_in' => 3600,
        ]);
    }

    #[TypedRoute(request: RegisterData::class, response: UserData::class)]
    public function register(RegisterData $data): JsonResponse
    {
        return response()->json([
            'id' => 1,
            'name' => $data->name,
            'email' => $data->email,
        ], 201);
    }

    #[TypedRoute(response: UserData::class)]
    public function me(): JsonResponse
    {
        return response()->json([
            'id' => 1,
            'name' => 'Current User',
            'email' => 'me@example.com',
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Logged out successfully']);
    }
}
