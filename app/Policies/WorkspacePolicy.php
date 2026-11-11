<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->members()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id && $this->view($user, $workspace);
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $this->update($user, $workspace);
    }
}
