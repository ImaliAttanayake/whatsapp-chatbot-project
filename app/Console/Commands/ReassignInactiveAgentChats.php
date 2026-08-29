<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\User;

class ReassignInactiveAgentChats extends Command
{
    protected $signature = 'whatsapp:reassign-inactive-chats';
    protected $description = 'Reassign chats of agents who are offline for more than 1 hour to active agents';

    public function handle()
    {
        // Find agents who have not sent a heartbeat for more than one hour.
        $oneHourAgo = now()->subHour();
        $inactiveAgentIds = User::where('last_activity_at', '<', $oneHourAgo)->pluck('id');

        if ($inactiveAgentIds->isEmpty()) {
            $this->info('No inactive agents found.');
            return;
        }

        // Find agents who have been active within the last five minutes.
        $onlineAgents = User::where('last_activity_at', '>=', now()->subMinutes(5))->get();

        if ($onlineAgents->isEmpty()) {
            $this->warn('No online agents available for reassignment.');
            return;
        }

        // Retrieve all chats assigned to inactive agents.
        $chatsToReassign = Contact::whereIn('assigned_agent_id', $inactiveAgentIds)->get();

        if ($chatsToReassign->isEmpty()) {
            $this->info('No chats found for inactive agents.');
            return;
        }

        // Distribute chats among online agents using round-robin assignment.
        $agentCount = $onlineAgents->count();
        $i = 0;

        foreach ($chatsToReassign as $chat) {
            $agent = $onlineAgents[$i];

            $chat->update([
                'assigned_agent_id' => $agent->id,
            ]);

            $this->info("Chat ID {$chat->id} reassigned to {$agent->name}");
            $i = ($i + 1) % $agentCount;
        }

        $this->info('Inactive chats reassigned successfully!');
    }
}
