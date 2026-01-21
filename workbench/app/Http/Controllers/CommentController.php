<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use Workbench\App\Data\CommentData;
use Workbench\App\Data\CreateCommentData;

final class CommentController extends Controller
{
    #[TypedRoute(response: CommentData::class, isCollection: true)]
    public function index(int $post): JsonResponse
    {
        return response()->json([
            ['id' => 1, 'body' => 'Great post!', 'post_id' => $post, 'user_id' => 1],
            ['id' => 2, 'body' => 'Thanks for sharing', 'post_id' => $post, 'user_id' => 2],
        ]);
    }

    #[TypedRoute(request: CreateCommentData::class, response: CommentData::class)]
    public function store(int $post, CreateCommentData $data): JsonResponse
    {
        return response()->json([
            'id' => 1,
            'body' => $data->body,
            'post_id' => $post,
            'user_id' => 1,
        ], 201);
    }

    #[TypedRoute(response: CommentData::class)]
    public function show(int $post, int $comment): JsonResponse
    {
        return response()->json([
            'id' => $comment,
            'body' => 'Great post!',
            'post_id' => $post,
            'user_id' => 1,
        ]);
    }

    public function destroy(int $post, int $comment): JsonResponse
    {
        return response()->json(null, 204);
    }
}
