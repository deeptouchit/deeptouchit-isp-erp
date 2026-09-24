<?php

namespace App\Policies;

use App\Models\ServerGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->can('servers.view');
    }

    public function view(User $user, ServerGroup $group): bool
    {
        return $user->role === 'admin' || $user->can('servers.view');
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->can('servers.create');
    }

    public function update(User $user, ServerGroup $group): bool
    {
        return $user->role === 'admin' || $user->can('servers.edit');
    }

    public function delete(User $user, ServerGroup $group): bool
    {
        return $user->role === 'admin' || $user->can('servers.delete');
    }
}
