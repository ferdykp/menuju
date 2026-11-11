<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function view(User $user, Guest $guest): bool
    {
        return $guest->workspace->members()
            ->where('users.id', $user->id)->wherePivot('status', 'active')->exists();
    }

    public function update(User $user, Guest $guest): bool
    {
        return $this->view($user, $guest);
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $this->view($user, $guest);
    }
}
