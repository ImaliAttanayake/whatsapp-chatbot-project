<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoundRobinAssignmentService
{
    public function assignIfUnassigned(Contact $contact): ?User
    {
        return DB::transaction(function () use ($contact): ?User {
            // Lock the contact row within the transaction to prevent race conditions
            $contact = Contact::query()
                ->whereKey($contact->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // If an agent is already assigned, do not assign again
            if ($contact->assigned_agent_id !== null) {
                return $contact->assignedAgent;
            }

            $now = Carbon::now();
            $startTime = Carbon::createFromTimeString('08:00:00');
            $endTime = Carbon::createFromTimeString('17:00:00');

            // Office hours check (08:00 AM - 05:00 PM)
            if ($now->between($startTime, $endTime)) {

                // Select the oldest or least recently assigned admin who is online via heartbeat
                // and active within the last 5 minutes
                $nextAdmin = User::where('is_admin', true)
                    ->whereNotNull('last_seen_at')
                    ->where('last_seen_at', '>=', $now->copy()->subMinutes(5)) // Heartbeat active window
                    ->orderByRaw('last_activity_at IS NULL DESC')
                    ->orderBy('last_activity_at', 'asc')
                    ->first();

                if ($nextAdmin) {
                    $contact->update([
                        'assigned_agent_id' => $nextAdmin->id,
                        'locked_by_user_id' => $nextAdmin->id, // Existing chat lock mechanism
                        'locked_at' => $now,
                    ]);

                    $nextAdmin->update([
                        'last_activity_at' => $now,
                    ]);

                    return $nextAdmin;
                }
            }

            return null;
        });
    }
}
