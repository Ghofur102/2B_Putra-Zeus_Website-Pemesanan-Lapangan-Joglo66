<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

trait FieldAccessTrait
{
    private function checkFieldAccess($user, $fieldId): bool
    {
        if ($user && $user->role === 'worker') {
            return DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $fieldId)
                ->exists();
        }
        return true;
    }

    private function getAccessibleFieldIds($user): array
    {
        if ($user && $user->role === 'worker') {
            return DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->pluck('fk_field_id')
                ->toArray();
        }
        return [];
    }

    private function ok($message, $data = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    private function fail($message, $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    private function notFound($message): JsonResponse
    {
        return $this->fail($message, 404);
    }

    private function forbidden($message): JsonResponse
    {
        return $this->fail($message, 403);
    }

    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data' => null,
            'errors' => $validator->errors(),
        ], 422);
    }
}
