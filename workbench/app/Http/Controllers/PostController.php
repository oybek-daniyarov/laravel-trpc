<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use Workbench\App\Data\CreatePostData;
use Workbench\App\Data\PostData;

final class PostController extends Controller
{
    #[TypedRoute(response: PostData::class, isCollection: true)]
    public function index(): JsonResponse
    {
        return response()->json([
            ['id' => 1, 'title' => 'First Post', 'content' => 'Hello World', 'user_id' => 1],
            ['id' => 2, 'title' => 'Second Post', 'content' => 'Another post', 'user_id' => 1],
        ]);
    }

    #[TypedRoute(response: PostData::class)]
    public function show(int $post): JsonResponse
    {
        return response()->json([
            'id' => $post,
            'title' => 'First Post',
            'content' => 'Hello World',
            'user_id' => 1,
        ]);
    }

    #[TypedRoute(request: CreatePostData::class, response: PostData::class)]
    public function store(CreatePostData $data): JsonResponse
    {
        return response()->json([
            'id' => 1,
            'title' => $data->title,
            'content' => $data->content,
            'user_id' => 1,
        ], 201);
    }

    public function destroy(int $post): JsonResponse
    {
        return response()->json(null, 204);
    }
}
