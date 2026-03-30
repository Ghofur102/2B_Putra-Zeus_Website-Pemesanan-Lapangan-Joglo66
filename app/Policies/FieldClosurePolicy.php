<?php

namespace App\Policies;

use App\Models\FieldClosure;
use App\Models\User;

class FieldClosurePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, int $fk_field_id): bool
    {
        return $user->fieldAdmin()->where('fk_field_id', $fk_field_id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FieldClosure $fieldClosure): bool
    {
        return $user->fieldAdmin()->where('fk_field_id', $fieldClosure->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, int $fk_field_id): bool
    {
        return $user->fieldAdmin()->where('fk_field_id', $fk_field_id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FieldClosure $fieldClosure): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FieldClosure $fieldClosure): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FieldClosure $fieldClosure): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FieldClosure $fieldClosure): bool
    {
        return false;
    }
}
