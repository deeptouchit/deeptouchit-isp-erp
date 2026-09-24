<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->can('servers.view');
    }

    public function view(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.view');
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->can('servers.create');
    }

    public function update(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.edit');
    }

    public function delete(User $user, Server $server): bool
    {
        if ($server->is_master) {
            return false; // Master node deletion strictly forbidden
        }

        return $user->role === 'admin' || $user->can('servers.delete');
    }

    public function verify(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.verify');
    }

    public function discover(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.discover');
    }

    public function sync(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.sync');
    }

    public function maintenance(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.maintenance');
    }

    public function manageServices(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.services');
    }

    public function manageCredentials(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.credentials') || $user->can('servers.edit');
    }

    public function approveHostKey(User $user, Server $server): bool
    {
        return $user->role === 'admin' || $user->can('servers.host_key') || $user->can('servers.verify');
    }
}
