<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->where('conversations.id', $conversationId)->exists()
        ? $user
        : false;
});
