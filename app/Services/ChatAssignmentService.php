<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Decides which single admin owns a chat.
 *
 * Without this every admin sees every incoming WhatsApp chat, so the same
 * message reaches all of them at once. Each new chat is handed to exactly one
 * online admin (the one carrying the fewest open chats); the rest only see it
 * while it is unassigned.
 */
class ChatAssignmentService
{
    /**
     * Give an unassigned chat to the least-loaded online admin.
     *
     * Returns the owning admin, or null when nobody is online - in that case the
     * chat stays unassigned and remains visible to everyone, so it is never
     * parked on an admin who has gone home.
     */
    public function autoAssign(Contact $contact): ?User
    {
        if (!config('chat.auto_assign', true)) {
            return null;
        }

        return DB::transaction(function () use ($contact) {
            /** @var Contact $locked */
            $locked = Contact::whereKey($contact->id)->lockForUpdate()->firstOrFail();

            if ($locked->assigned_admin_id) {
                $contact->setRawAttributes($locked->getAttributes(), true);

                return $locked->assignedAdmin;
            }

            $admin = $this->pickAdmin();

            if (!$admin) {
                return null;
            }

            $locked->forceFill([
                'assigned_admin_id' => $admin->id,
                'assigned_at' => now(),
            ])->save();

            $contact->setRawAttributes($locked->getAttributes(), true);
            $contact->setRelation('assignedAdmin', $admin);

            Log::info('Chat auto-assigned', [
                'contact_id' => $locked->id,
                'contact_mobile' => $locked->mobile,
                'admin_id' => $admin->id,
            ]);

            return $admin;
        });
    }

    /**
     * Move a chat to another admin, or release it back to the shared pool when
     * $admin is null.
     */
    public function assignTo(Contact $contact, ?User $admin): void
    {
        $contact->forceFill([
            'assigned_admin_id' => $admin?->id,
            'assigned_at' => $admin ? now() : null,
        ])->save();

        // A transferred chat must not stay locked by the previous owner,
        // otherwise the new owner cannot reply until the lock TTL expires.
        if ($contact->locked_by_user_id && $contact->locked_by_user_id !== $admin?->id) {
            $contact->forceFill([
                'locked_by_user_id' => null,
                'locked_at' => null,
            ])->save();
        }

        $contact->setRelation('assignedAdmin', $admin);

        Log::info('Chat assignment changed', [
            'contact_id' => $contact->id,
            'admin_id' => $admin?->id,
            'by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Admins a chat may be handed to.
     */
    public function assignableAdmins(): Collection
    {
        return User::query()
            ->whereIn('role', ['admin', 'superadmin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'last_seen_at'])
            ->each(fn (User $admin) => $admin->is_online = $this->isOnline($admin));
    }

    /**
     * True when this user may open the chat at all.
     */
    public function canView(Contact $contact, User $user): bool
    {
        if ($user->role === 'superadmin') {
            return true;
        }

        return $contact->assigned_admin_id === null
            || $contact->assigned_admin_id === $user->id;
    }

    public function isOnline(User $user): bool
    {
        return $user->last_seen_at !== null
            && $user->last_seen_at->gt(now()->subMinutes($this->onlineWindowMinutes()));
    }

    /**
     * Least-loaded online admin, tie-broken by who has been idle longest so the
     * work rotates instead of piling onto whoever logged in first.
     */
    private function pickAdmin(): ?User
    {
        return User::query()
            ->whereIn('role', ['admin', 'superadmin'])
            ->where('last_seen_at', '>=', now()->subMinutes($this->onlineWindowMinutes()))
            ->withCount(['assignedChats as open_chats_count' => function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('human_handoff_status')
                        ->orWhereNotIn('human_handoff_status', ['resolved']);
                });
            }])
            ->withMax('assignedChats as last_assigned_at', 'assigned_at')
            // Fewest open chats first; then whoever went longest without a new
            // one (NULL = never assigned, which sorts first).
            ->orderBy('open_chats_count')
            ->orderBy('last_assigned_at')
            ->orderBy('id')
            ->first();
    }

    private function onlineWindowMinutes(): int
    {
        return max(1, (int) config('chat.online_window_minutes', 5));
    }
}
