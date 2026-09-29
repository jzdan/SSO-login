<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserInfoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->tokenCan('email') ? $user->email : null,
            'email_verified' => (bool) $user->email_verified_at,
            'is_admin' => $user->is_admin,
            'updated_at' => $user->updated_at?->toIso8601String(),
        ]);
    }
}
