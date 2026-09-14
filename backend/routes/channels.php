<?php

use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('plot.{plotId}', function ($user, $plotId) {
    $plot = Plot::with('farm')->find((int) $plotId);

    if (!$plot || !$plot->farm) {
        return false;
    }

    return (int) $plot->farm->user_id === (int) $user->id;
});
