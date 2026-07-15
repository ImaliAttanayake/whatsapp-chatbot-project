<?php

return [
    // How long a chat lock is valid without a heartbeat (seconds)
    'lock_ttl_seconds' => (int) env('CHAT_LOCK_TTL_SECONDS', 1800),
    // How many chats to show in the sidebar list
    'list_limit' => (int) env('CHAT_LIST_LIMIT', 40),
    // How many recent mobiles to request per sync run
    'sync_recent_limit' => (int) env('CHAT_SYNC_RECENT_LIMIT', 40),
    // Safety cap for incoming sync limit values
    'sync_recent_max_limit' => (int) env('CHAT_SYNC_RECENT_MAX_LIMIT', 200),
    // How many recent messages to inspect when refreshing sidebar notification state
    'state_sync_message_limit' => (int) env('CHAT_STATE_SYNC_MESSAGE_LIMIT', 15),
    // Prevent repeated sidebar state sync calls from hammering the SLT API
    'state_sync_cooldown_seconds' => (int) env('CHAT_STATE_SYNC_COOLDOWN_SECONDS', 20),
    // Customer phrases that should pause the bot and push the chat to a human agent
    'human_handoff_keywords' => array_values(array_filter(array_map(
        static fn (string $value) => trim($value),
        explode(',', (string) env(
            'CHAT_HUMAN_HANDOFF_KEYWORDS',
            'human agent,live agent,talk to human,real person,need human,speak to agent'
        ))
    ))),
    // A recurring error is marked active if seen within this window (minutes)
    'error_active_window_minutes' => (int) env('CHAT_ERROR_ACTIVE_WINDOW_MINUTES', 5),
];
