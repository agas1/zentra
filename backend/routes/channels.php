<?php

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('demands', function (User $user) {
    return in_array($user->role, ['admin', 'atendimento', 'designer']);
});

Broadcast::channel('demand.{demandId}', function (User $user, int $demandId) {
    $demand = \App\Domain\Demand\Models\Demand::find($demandId);
    if (!$demand) return false;

    if (in_array($user->role, ['admin', 'atendimento'])) return true;
    if ($user->role === 'designer') return $demand->assigned_designer_id === $user->id;
    if ($user->role === 'cliente') return $demand->client_id === $user->client_id;

    return false;
});

Broadcast::channel('client.{clientId}', function (User $user, int $clientId) {
    if ($user->role === 'cliente') {
        return $user->client_id === $clientId;
    }
    return in_array($user->role, ['admin', 'atendimento', 'designer']);
});
