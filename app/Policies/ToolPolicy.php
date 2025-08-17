<?php

namespace App\Policies;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ToolPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, Tool $tool)
    {
        return $user->belongsToTeam($tool->team);
    }

    public function create(User $user)
    {
        return $user->currentTeam !== null;
    }

    public function update(User $user, Tool $tool)
    {
        return $user->belongsToTeam($tool->team);
    }

    public function delete(User $user, Tool $tool)
    {
        return $user->belongsToTeam($tool->team);
    }

    public function execute(User $user, Tool $tool)
    {
        return $user->belongsToTeam($tool->team) && $tool->is_active;
    }
}
