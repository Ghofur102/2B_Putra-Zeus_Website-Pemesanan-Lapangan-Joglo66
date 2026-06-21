<?php

namespace App\Http\Controllers\Traits;

use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

trait FieldAccessTrait
{
    protected function checkFieldAccess($user, $fieldId): bool
    {
        $hasAccess = true;

        if ($user && $user->role === UserRole::WORKER->value) {
            $hasAccess = DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $fieldId)
                ->exists();
        }

        return $hasAccess;
    }

    protected function getAccessibleFieldIds($user): array
    {
        $accessibleIds = [];

        if ($user && $user->role === UserRole::WORKER->value) {
            $accessibleIds = DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->pluck('fk_field_id')
                ->toArray();
        }

        return $accessibleIds;
    }

    protected function ok($message, $data = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    protected function fail($message, $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], $statusCode);
    }

    protected function notFound($message): JsonResponse
    {
        return $this->fail($message, 404);
    }

    protected function forbidden($message): JsonResponse
    {
        return $this->fail($message, 403);
    }

    protected function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => null,
            'errors'  => $validator->errors(),
        ], 422);
    }
}
