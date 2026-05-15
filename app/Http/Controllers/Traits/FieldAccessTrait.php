<?php

namespace App\Http\Controllers\Traits;

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
}
