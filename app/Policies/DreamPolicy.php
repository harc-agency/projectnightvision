<?php

namespace App\Policies;

use App\Models\Dream;
use App\Models\User;

class DreamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dream $dream): bool
    {
        // only show if the user is the owner of the dream
        return $user->dreams->contains('id', $dream->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // anyone can create a dream
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dream $dream): bool
    {
        // only the owner of the dream can update it
        return $user->id === $dream->user_id || $user->isAdmin;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dream $dream): bool
    {
        // only the owner of the dream can delete it
        return $user->id === $dream->user_id || $user->isAdmin;

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dream $dream): bool
    {
        // only the owner of the dream can restore it
        return $user->id === $dream->user_id || $user->isAdmin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dream $dream): bool
    {
        // only admins or the user can permanently delete a dream
        return $user->id === $dream->user_id || $user->isAdmin;
    }
}
