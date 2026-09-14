<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('plot.{plotId}', function ($user, $plotId) {
    // For now, allow any authenticated user to listen to plot channels
    return true; 
});
