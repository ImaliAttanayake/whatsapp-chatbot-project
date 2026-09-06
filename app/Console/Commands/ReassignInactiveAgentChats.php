<?php

namespace App\Console\Commands;

use App\Models\ChatQueue;
use App\Models\Contact;
use App\Models\User;
use App\Services\RoundRobinAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReassignInactiveAgentChats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:reassign-inactive-agent-chats
                            {--minutes=5 : Chat inactivity threshold in minutes}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'chats:reassign-inactive',
        'chats:reassign-inactive-agents',
        'reassign:inactive-agent-chats',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect chats with no customer/agent activity in the last N minutes and reassign or queue them';

    /**
     * Execute the console command.
     */
    public function handle(RoundRobinAssignmentService $router): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $inactivityThreshold = now()->subMinutes($minutes);

        $this->info("Checking for chats inactive since: {$inactivityThreshold->toDateTimeString()} ({$minutes}-minute inactivity threshold)");

        $reassignedCount = 0;
        $queuedCount     = 0;
        $unlockedCount   = 0;

        DB::transaction(function () use ($inactivityThreshold, $minutes, $router, &$reassignedCount, &$queuedCount, &$unlockedCount) {

            // ---------------------------------------------------------------
            // 1. Find assigned chats with no activity in the last N minutes
            // ---------------------------------------------------------------
            $staleChats = Contact::query()
                ->whereNotNull('human_handoff_assigned_user_id')
                ->where('human_handoff_status', 'assigned_to_agent')
                ->where('updated_at', '<=', $inactivityThreshold)
                ->lockForUpdate()
                ->get();

            foreach ($staleChats as $contact) {
                // Unassign from current (inactive) agent
                $contact->assigned_agent_id              = null;
                $contact->human_handoff_assigned_user_id = null;
                $contact->human_handoff_assigned_at      = null;

                // Try to route immediately to an available agent
                $agent = $router->assignAgent($contact);

                if ($agent) {
                    // assignAgent() already persists the assignment; just update status
                    $contact->human_handoff_status = 'assigned_to_agent';
                    $contact->save();

                    $reassignedCount++;
                    Log::info("[ReassignInactive] Contact #{$contact->id} reassigned to agent #{$agent->id} after {$minutes} min inactivity.");
                } else {
                    // No agent available — push into the FIFO queue
                    $contact->human_handoff_status = 'needs_human';
                    $contact->save();

                    ChatQueue::enqueue($contact->id);

                    $queuedCount++;
                    Log::info("[ReassignInactive] Contact #{$contact->id} pushed to chat_queues after {$minutes} min inactivity (no agents available).");
                }
            }

            // ---------------------------------------------------------------
            // 2. Release stale chat locks (held longer than the inactivity threshold)
            // ---------------------------------------------------------------
            $staleLockedContacts = Contact::query()
                ->whereNotNull('locked_by_user_id')
                ->where('locked_at', '<=', $inactivityThreshold)
                ->lockForUpdate()
                ->get();

            foreach ($staleLockedContacts as $contact) {
                $contact->locked_by_user_id = null;
                $contact->locked_at         = null;
                $contact->save();

                $unlockedCount++;
                Log::info("[ReassignInactive] Released stale lock on contact #{$contact->id} (lock held > {$minutes} min).");
            }
        });

        $this->info("Done: {$reassignedCount} reassigned, {$queuedCount} queued, {$unlockedCount} locks released.");

        return self::SUCCESS;
    }
}
