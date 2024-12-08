<?php

use App\Models\Integration;
use App\Models\Knowledge;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('integration.{id}', function ($user, $id) {
    return $user->current_team_id === Integration::find($id)->team_id;
});

Broadcast::channel('team.{id}.knowledges', function ($user, $id) {
    return (int) $user->current_team_id === (int) $id;
});

Broadcast::channel('team.{id}.knowledges.{knowledgeId}', function ($user, $id, $knowledgeId) {
    return (int) $user->current_team_id === (int) Knowledge::find($knowledgeId)->team_id;
});
