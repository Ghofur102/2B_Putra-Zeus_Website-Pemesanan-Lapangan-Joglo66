<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, array $fieldId): bool
    {
        // Allow admin to create bookings for testing
        if ($user->role === 'admin' || $user->role === 'manager' || $user->role === 'owner') {
            return true;
        }

        $uniqueFieldsId = array_unique($fieldId);
        $countOwned = $user->fieldAdmin()->whereIn('fk_field_id', $uniqueFieldsId)->count();

        return $user->role === 'tenant' || count($uniqueFieldsId) === $countOwned;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return false;
    }
}
