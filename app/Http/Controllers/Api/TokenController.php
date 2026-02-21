<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateApiTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->select(['id', 'name', 'last_used_at', 'created_at'])->latest()->get();

        return response()->json(['data' => $tokens]);
    }

    public function store(CreateApiTokenRequest $request): JsonResponse
    {
        $token = $request->user()->createToken($request->string('name')->toString(), ['workspace-api']);

        return response()->json([
            'message' => 'Token created. Copy it now; it will not be shown again.',
            'token' => $token->plainTextToken,
        ], 201);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $request->user()->tokens()->whereKey($tokenId)->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}
